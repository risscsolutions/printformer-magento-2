<?php
namespace Rissc\Printformer\Helper\Quote;

use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;
use Rissc\Printformer\Helper\Url as UrlHelper;
use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Rissc\Printformer\Helper\Api as ApiHelper;

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

        /** @var \Rissc\Printformer\Block\Custom\Editor\Link $viewBlock */
        $viewBlock = $block->getLayout()->createBlock('Rissc\Printformer\Block\Custom\Editor\Link');
        $viewBlock->setTemplate('Rissc_Printformer::custom/editor/view.phtml');
        $viewBlock->addData([
            'quote_item' => $quoteItem,
            'product' => $product,
            'editor_link' => $this->_getEditorUrl($quoteItem),
            'printformer_block' => $printFormerBlock
        ]);

        return $viewBlock->toHtml();
    }

    /**
     * @param      $quoteItem
     * @param      $product
     * @param string $userId
     *
     * @return string
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _getEditorUrl($quoteItem)
    {
        $buyRequest = $quoteItem->getBuyRequest();

        /*$editorUrl = $this->_urlHelper
            ->setStoreId($quoteItem->getPrintformerStoreid())
            ->getEditorUrl(
                $product->getId(),
                $product->getPrintformerProduct(),
                $buyRequest->getPrintformerIntent(),
                $userId,
                [
                    'draft_id' => $buyRequest->getData('printformer_draftid'),
                    'session_id' => $buyRequest->getData('printformer_unique_session_id')
                ]
            );*/
        $draftProcess = $this->_apiHelper->draftProcess(null, $buyRequest->getData('printformer_draftid'));
        $params = [
            'master_id' => $draftProcess->getMasterId(),
            'product_id' => $draftProcess->getProductId(),
            'data' => [
                'draft_process' => $draftProcess->getId(),
                'draft_hash' => $draftProcess->getDraftId(),
                'callback_url' => $this->_urlBuilder->getUrl("sales/order/view", [
                    'order_id' => $quoteItem->getId()
                ])
            ]
        ];

        $editorUrl = $this->_apiHelper->getEditorWebtokenUrl($draftProcess->getDraftHash(),
            $draftProcess->getUserIdentifier(), $params);

        $request = $this->_request;
        $route = $request->getModuleName() . '/' . $request->getControllerName() . '/' . $request->getActionName();

        if($this->_appState->getAreaCode() == Area::AREA_ADMINHTML) {
            $referrerUrl = $this->_backendUrl->getUrl($route, ['quote_id' => $request->getParam('quote_id')]);
        } else {
            $referrerUrl = $this->_urlBuilder->getUrl($route, ['quote_id' => $request->getParam('quote_id')]);
        }
        return $editorUrl .
            (strpos($editorUrl, '?') ? '&amp;' : '?') .
            'custom_referrer=' . urlencode($referrerUrl);
    }
}