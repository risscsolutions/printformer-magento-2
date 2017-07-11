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
use Rissc\Printformer\Helper\Session as SessionHelper;

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

    /** @var DraftFactory */
    protected $_draftFactory;

    /** @var SessionHelper */
    protected $_sessionHelper;

    public function __construct(
        Context $context,
        DraftGateway $draftGateway,
        UrlHelper $urlHelper,
        ProductFactory $productFactory,
        StoreManagerInterface $storeManager,
        DraftFactory $draftFactory,
        SessionHelper $sessionHelper
    )
    {
        $this->_draftGateway = $draftGateway;
        $this->_urlHelper = $urlHelper;
        $this->_productFactory = $productFactory;
        $this->_storeManager = $storeManager;
        $this->_draftFactory = $draftFactory;
        $this->_sessionHelper = $sessionHelper;

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

        $intent = $this->getRequest()->getParam('intent');
        $draftID = null;
        $this->_sessionHelper->setCurrentIntent($intent);

        $draftExists = false;
        $sessionUniqueId = $this->_sessionHelper->getCustomerSession()->getSessionUniqueID();
        if($sessionUniqueId)
        {
            $uniqueExplode = explode(':', $sessionUniqueId);
            if (isset($uniqueExplode[1]) && $product->getId() == $uniqueExplode[1])
            {
                $uniqueID = $this->_sessionHelper->getCustomerSession()->getSessionUniqueID();
                /** @var Draft $draftProcess */
                $draftProcess = $this->_draftFactory->create();
                $draftCollection = $draftProcess->getCollection()
                    ->addFieldToFilter('session_unique_id', ['eq' => $uniqueID])
                    ->addFieldToFilter('intent', ['eq' => $intent]);
                if ($draftCollection->count() == 1)
                {
                    /** @var Draft $draft */
                    $draft = $draftCollection->getFirstItem();
                    if ($draft->getId())
                    {
                        $draftExists = true;
                        $draftID = $draft->getDraftId();
                        $this->_sessionHelper->setCurrentIntent($draft->getIntent());
                    }
                }
            }
        }

        $isEdit = true;
        if($product->getId() && !$draftID) {
            $draftID = $this->_draftGateway->createDraft($product->getPrintformerProduct(), $intent);
            $isEdit = false;
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

        if(!$draftExists)
        {
            /** @var Draft $draftProcess */
            $draftProcess = $this->_draftFactory->create();
            $draftProcess->addData([
                'draft_id' => $draftID,
                'store_id' => $this->_storeManager->getStore()->getId(),
                'intent' => $intent,
                'session_unique_id' => null,
                'product_id' => $product->getId(),
                'customer_id' => $this->_sessionHelper->getCustomerSession()->getCustomerId(),
                'created_at' => time()
            ]);
            $draftProcess->getResource()->save($draftProcess);

            if (!$draftProcess->getId())
            {
                $this->messageManager->addNoticeMessage(__('We could not save your Draft. Please try again.'));
                echo '
                <script type="text/javascript">
                    window.top.location.href = \'' . $this->_redirect->getRefererUrl() . '\'
                </script>
                ';
                die();
            }
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

        /**
         * TODO aka: maybe remove the "edit" thingy
         */
        $redirectUrl = $urlParts[0] . ($isEdit ? /*'/edit'*/ '' : '') . '?' . implode('&', $queryArray);

        $redirect->setUrl($redirectUrl);

        return $redirect;
    }
}