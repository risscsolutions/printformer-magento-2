<?php
namespace Rissc\Printformer\Helper\Quote;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;
use Rissc\Printformer\Helper\Api\Url as UrlHelper;
use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Rissc\Printformer\Helper\Api as ApiHelper;
use Rissc\Printformer\Setup\InstallSchema;

class View
    extends AbstractHelper
{
    /** @var UrlHelper */
    protected $_urlHelper;

    /** @var StoreManagerInterface */
    protected $_storeManager;

    /** @var BackendUrlInterface */
    protected $_backendUrl;

    /** @var State */
    protected $_appState;

    /** @var ApiHelper */
    protected $_apiHelper;

    public function __construct(
        Context $context,
        UrlHelper $urlHelper,
        StoreManagerInterface $storeManager,
        BackendUrlInterface $backendUrl,
        State $appState,
        ApiHelper $apiHelper
    )
    {
        $this->_urlHelper = $urlHelper;
        $this->_storeManager = $storeManager;
        $this->_backendUrl = $backendUrl;
        $this->_appState = $appState;
        $this->_apiHelper = $apiHelper;

        parent::__construct($context);
    }

    /**
     * @param      $quoteItem
     * @param      $product
     * @param      $block
     * @param null $userId
     *
     * @return string
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getEditorView($quoteItem, $product, $block, $userId = null)
    {
        /** @var \Rissc\Printformer\Block\Catalog\Product\View\Printformer $printFormerBlock */
        $printFormerBlock = $block->getLayout()->createBlock('Rissc\Printformer\Block\Catalog\Product\View\Printformer');

        $editors = $this->_getEditorUrl($quoteItem);
        $viewHtml = '';
        if (is_array($editors)) {
            $counter = 0;
            foreach($editors as $editor) {
                /** @var \Rissc\Printformer\Block\Custom\Editor\Link $viewBlock */
                $viewBlock = $block->getLayout()->createBlock('Rissc\Printformer\Block\Custom\Editor\Link');
                $viewBlock->setTemplate('Rissc_Printformer::custom/editor/view.phtml');
                $viewBlock->setName('editor.data.item_' . $counter);
                $viewBlock->addData([
                    'quote_item' => $quoteItem,
                    'product' => $product,
                    'editor_link' => $editor['url'],
                    'printformer_block' => $printFormerBlock,
                    'draft_hash' => $editor['hash'],
                    'pos_counter' => $counter + 1
                ]);

                $viewHtml .= $viewBlock->toHtml();
                $counter++;
            }
        }

        return $viewHtml;
    }

    /**
     * @param Item $quoteItem
     *
     * @return string|array
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _getEditorUrl($quoteItem)
    {
        $editorUrls = [];
        $buyRequest = $quoteItem->getBuyRequest();
        $product = $quoteItem->getProduct();
        $product->getResource()->load($product, $product->getId());

        if (!empty($buyRequest->getData(InstallSchema::COLUMN_NAME_DRAFTID))){
            $draftIds = $buyRequest->getData(InstallSchema::COLUMN_NAME_DRAFTID);
            if (!empty($draftIds)) {
                $draftHashes = explode(',', $draftIds);

                foreach($draftHashes as $draftHash) {
                    $draftProcess = $this->_apiHelper->draftProcess($draftHash);
                    $editorUrl = $this->_urlHelper->getEditorEntry(
                        $draftProcess->getProductId(),
                        $product->getPrintformerProduct(),
                        $draftProcess->getDraftId(),
                        null,
                        $draftProcess->getIntent()
                    );

                    $request = $this->_request;
                    $route = $request->getModuleName() . '/' .
                        $request->getControllerName() . '/' .
                        $request->getActionName();

                    if ($this->_appState->getAreaCode() == Area::AREA_ADMINHTML) {
                        $referrerUrl = $this->_backendUrl->getUrl(
                            $route,
                            ['order_id' => $request->getParam('order_id')]
                        );
                    } else {
                        $referrerUrl = $this->_urlBuilder->getUrl(
                            $route,
                            ['quote_id' => $request->getParam('quote_id')]
                        );
                    }

                    $editorUrls[] = ['url' => $editorUrl .
                        (strpos($editorUrl, '?') ? '&amp;' : '?') .
                        'custom_referrer=' . urlencode($referrerUrl), 'hash' => $draftProcess->getDraftId()];
                }
            }
        }

        return $editorUrls;
    }
}