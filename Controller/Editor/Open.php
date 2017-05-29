<?php
namespace Rissc\Printformer\Controller\Editor;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Rissc\Printformer\Gateway\User\Draft as DraftGateway;
use Rissc\Printformer\Helper\Url as UrlHelper;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;
use Rissc\Printformer\Model\DraftFactory;
use Rissc\Printformer\Model\Draft;

class Open extends Action
{
    /** @var DraftGateway */
    protected $_draftGateway;

    /** @var UrlHelper */
    protected $_urlHelper;

    /** @var ProductFactory */
    protected $_productFactory;

    /** @var StoreManagerInterface */
    protected $_storeManager;


    protected $_draftFactory;

    public function __construct(
        Context $context,
        DraftGateway $draftGateway,
        UrlHelper $urlHelper,
        ProductFactory $productFactory,
        StoreManagerInterface $storeManager,
        DraftFactory $draftFactory
    )
    {
        $this->_draftGateway = $draftGateway;
        $this->_urlHelper = $urlHelper;
        $this->_productFactory = $productFactory;
        $this->_storeManager = $storeManager;
        $this->_draftFactory = $draftFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        if(!$this->getRequest()->getParam('product_id'))
        {
            $this->messageManager->addNoticeMessage(__('We could not determine the right Parameters. Please try again.'));
            echo '
                <script type="text/javascript">
                    window.top.location.href = \'' . $this->_redirect->getRefererUrl() . '\'
                </script>
            ';
            die();
        }

        $redirect = $this->resultRedirectFactory->create();

        /** @var Product $product */
        $product = $this->_productFactory->create();
        $product->getResource()->load($product, $this->getRequest()->getParam('product_id'));

        $draftID = null;
        if($product->getId()) {
            $intent = $this->getRequest()->getParam('intent');
            $draftID = $this->_draftGateway->createDraft($product->getPrintformerProduct(), $intent);
        }

        if(!$draftID) {
            $this->messageManager->addNoticeMessage(__('We could not determine the right Parameters. Please try again.'));
            echo '
                <script type="text/javascript">
                    window.top.location.href = \'' . $this->_redirect->getRefererUrl() . '\'
                </script>
            ';
            die();
        }

        /** @var Draft $draftProcess */
        $draftProcess = $this->_draftFactory->create();
        $draftProcess->addData([
            'draft_id' => $draftID,
            'store_id' => $this->_storeManager->getStore()->getId(),
            'created_at' => time()
        ]);
        $draftProcess->getResource()->save($draftProcess);

        if(!$draftProcess->getId()) {
            $this->messageManager->addNoticeMessage(__('We could not save your Draft. Please try again.'));
            echo '
                <script type="text/javascript">
                    window.top.location.href = \'' . $this->_redirect->getRefererUrl() . '\'
                </script>
            ';
            die();
        }

        $editorUrl = $this->_urlHelper->getDraftEditorUrl($draftID);

        $queryParams = array_merge($this->_request->getParams(), [
            'store_id' => $this->_storeManager->getStore()->getId(),
            'draft_process' => $draftProcess->getId()
        ]);

        $referrer = $this->_url->getUrl('printformer/editor/save', $queryParams);
        $encodedUrl = urlencode(base64_encode($referrer));

        $urlParts = explode('?', $editorUrl);

        $parsedQuery = [];
        parse_str($urlParts[1], $parsedQuery);

        $parsedQuery['callback'] = $encodedUrl;
        $queryArray = [];
        foreach($parsedQuery as $key => $value)
        {
            $queryArray[] = $key . '=' . $value;
        }

        $redirectUrl = $urlParts[0] . '?' . implode('&', $queryArray);

        $redirect->setUrl($redirectUrl);

        return $redirect;
    }
}