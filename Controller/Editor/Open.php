<?php

namespace Rissc\Printformer\Controller\Editor;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;
use Rissc\Printformer\Gateway\User\Draft as DraftGateway;
use Rissc\Printformer\Helper\Url as UrlHelper;
use Rissc\Printformer\Model\DraftFactory;
use Rissc\Printformer\Model\Draft;
use Rissc\Printformer\Helper\Session as SessionHelper;
use Rissc\Printformer\Helper\Editor\Preselect as PreselectHelper;


class Open extends Action
{
    /**
     * @var DraftGateway
     */
    protected $_draftGateway;

    /**
     * @var UrlHelper
     */
    protected $_urlHelper;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var DraftFactory
     */
    protected $_draftFactory;

    /**
     * @var SessionHelper
     */
    protected $_sessionHelper;

    /**
     * @var PreselectHelper
     */
    protected $_preselectHelper;

    /**
     * Open constructor.
     * @param Context $context
     * @param DraftGateway $draftGateway
     * @param UrlHelper $urlHelper
     * @param ProductFactory $productFactory
     * @param StoreManagerInterface $storeManager
     * @param DraftFactory $draftFactory
     * @param SessionHelper $sessionHelper
     * @param PreselectHelper $preselectHelper
     */
    public function __construct(
        Context $context,
        DraftGateway $draftGateway,
        UrlHelper $urlHelper,
        ProductFactory $productFactory,
        StoreManagerInterface $storeManager,
        DraftFactory $draftFactory,
        SessionHelper $sessionHelper,
        PreselectHelper $preselectHelper
    ) {
        $this->_draftGateway = $draftGateway;
        $this->_urlHelper = $urlHelper;
        $this->_productFactory = $productFactory;
        $this->_storeManager = $storeManager;
        $this->_draftFactory = $draftFactory;
        $this->_sessionHelper = $sessionHelper;
        $this->_preselectHelper = $preselectHelper;

        parent::__construct($context);
    }

    public function execute()
    {
        /**
         * Get all params and variables needed
         */
        $params           = $this->getRequest()->getParams();
        $productId        = $this->getRequest()->getParam('product_id');
        $intent           = $this->getRequest()->getParam('intent');
        $printformerDraft = $this->getRequest()->getParam('draft_id');
        $sessionUniqueId  = $this->getRequest()->getParam('session_id');
        $requestReferrer  = $this->getRequest()->getParam('custom_referrer');
        $storeId          = $this->_storeManager->getStore()->getId();
        $customerSession  = $this->_sessionHelper->getCustomerSession();

        /**
         * Show an error if product id was not set
         */
        if(!$productId) {
            $this->_die(__('We could not determine the right Parameters. Please try again.'));
        }

        /**
         * Save preselected data
         */
        if($this->getRequest()->isPost()) {
            $this->_savePreselectedData($this->getRequest()->getParams());
        }

        /**
         * Load product and save intent to session data
         */
        /** @var Product $product */
        $product = $this->_productFactory->create()->load($productId);
        $this->_sessionHelper->setCurrentIntent($intent);

        /**
         * Try to load draft from database
         */
        $draftProcess = $this->_getDraftProcess($sessionUniqueId, $product, $intent, $printformerDraft, $storeId, $customerSession);

        /**
         * If draft could not be loaded from database, create it
         */
        if(!$draftProcess->getId()) {
            $draftProcess = $this->_createDraftProcess($product, $storeId, $intent, $customerSession->getCustomerId());
        }

        /**
         * If draft could not be created or loaded, show an error
         */
        if(!$draftProcess->getId()) {
            $this->_die(__('We could not determine the right Parameters. Please try again.'));
        }

        /**
         * Get printformer editor url by draft id
         */
        $editorUrl = $this->_urlHelper->getDraftEditorUrl($draftProcess->getDraftId(), $this->_draftGateway->isV2Enabled());

        /**
         * Build redirect url
         */
        $redirectUrl = $this->_buildRedirectUrl($editorUrl, $requestReferrer, $draftProcess, $customerSession,
            $storeId, $params);

        if($this->_draftGateway->isV2Enabled()) {
            if($customerSession->getCustomerId() == null) {
                $this->_draftGateway->setUserIdentifier($draftProcess->getUserIdentifier());
            }
            $redirectUrl = $this->_draftGateway->getRedirectUrl($redirectUrl);
        }

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setUrl($redirectUrl);

        return $redirect;
    }

    /**
     * Add preselect data to session
     * @param array $data
     * @return void
     */
    protected function _savePreselectedData($data)
    {
        $preselectData = $this->_preselectHelper->getPreselectArray($data);

        if (!empty($preselectData)) {
            $this->_sessionHelper->getCatalogSession()->setSavedPrintformerOptions($preselectData);
        }
    }

    /**
     * Add notice to message manager and die()
     * @param \Magento\Framework\Phrase $notice
     * @return void
     */
    protected function _die($notice)
    {
        $this->messageManager->addNoticeMessage($notice);
        echo '
                <script type="text/javascript">
                    window.top.location.href = \'' . $this->_redirect->getRefererUrl() . '\'
                </script>
            ';
        die();
    }

    /**
     * @param Product $product
     * @param int $storeId
     * @param string $intent
     * @param int $customerId
     * @param string $sessionUniqueId
     * @return Draft
     */
    protected function _createDraftProcess(Product $product, $storeId, $intent, $customerId, $sessionUniqueId = null)
    {
        $draftId = $this->_draftGateway->createDraft($product->getPrintformerProduct(), $intent);

        $userIdentifier = NULL;
        if($this->_draftGateway->isV2Enabled()) {
            $userIdentifier = $this->_draftGateway->getUserIdentifier();
        }

        /** @var Draft $draftProcess */
        $draftProcess = $this->_draftFactory->create();
        $draftProcess->addData([
            'draft_id' => $draftId,
            'store_id' => $storeId,
            'intent' => $intent,
            'session_unique_id' => $sessionUniqueId,
            'product_id' => $product->getId(),
            'customer_id' => $customerId,
            'user_identifier' => $userIdentifier,
            'created_at' => time()
        ]);
        $draftProcess->getResource()->save($draftProcess);

        if (!$draftProcess->getId()) {
            $this->_die(__('We could not save your draft. Please try again.'));
        }

        return $draftProcess;
    }

    /**
     * @param string $sessionUniqueId
     * @param Product $product
     * @param string $intent
     * @param string $printformerDraft
     * @param int $storeId
     * @param CustomerSession $customerSession
     * @return Draft
     */
    protected function _getDraftProcess($sessionUniqueId, $product, $intent, $printformerDraft, $storeId, $customerSession)
    {
        /** @var Draft $draftProcess */
        $draftProcess = $this->_draftFactory->create();

        if($sessionUniqueId == null) {
            $sessionUniqueId = $customerSession->getSessionUniqueID();
        }

        if($sessionUniqueId) {
            $uniqueExplode = explode(':', $sessionUniqueId);
            if (isset($uniqueExplode[1]) && $product->getId() == $uniqueExplode[1]) {
                $draftCollection = $draftProcess->getCollection()
                    ->addFieldToFilter('session_unique_id', ['eq' => $sessionUniqueId])
                    ->addFieldToFilter('intent', ['eq' => $intent]);
                if ($printformerDraft != null) {
                    $draftCollection->addFieldToFilter('draft_id', ['eq' => $printformerDraft]);
                }
                if ($draftCollection->count() == 1) {
                    if($draftCollection->getFirstItem()->getUserIdentifier() == $this->_draftGateway->getUserIdentifier()
                        || $this->_sessionHelper->getCustomerId() == null) {
                        /** @var Draft $draft */
                        $draftProcess = $draftCollection->getFirstItem();
                        if ($draftProcess->getId() && $draftProcess->getDraftId()) {
                            $this->_sessionHelper->setCurrentIntent($draftProcess->getIntent());
                        }
                    }
                } else {
                    $draftProcess = $draftCollection->getLastItem();
                }
            }
        }

        return $draftProcess;
    }

    /**
     * @param string $requestReferrer
     * @param Draft $draftProcess
     * @param int $storeId
     * @param array $params
     * @return string
     */
    protected function _getCallbackUrl($requestReferrer, Draft $draftProcess, $storeId = 0, $params = [], $encodeUrl = true)
    {
        if($requestReferrer != null) {
            $referrer = urldecode($requestReferrer);
        } else {
            $referrerParams = array_merge($params, [
                'store_id'      => $storeId,
                'draft_process' => $draftProcess->getId()
            ]);

            if(isset($params['quote_id']) && isset($params['product_id'])) {
                $referrerParams['quote_id'] = $params['quote_id'];
                $referrerParams['edit_product'] = $params['product_id'];
                $referrerParams['is_edit'] = 1;
            }

            $referrer = $this->_url->getUrl('printformer/editor/save', $referrerParams);
        }

        if($encodeUrl) {
            $referrer = urlencode(base64_encode($referrer));
        }

        return $referrer;
    }

    /**
     * @param string $editorUrl
     * @param string $requestReferrer
     * @param Draft $draftProcess
     * @param CustomerSession $customerSession
     * @param int $storeId
     * @param array $params
     * @return string
     */
    protected function _buildRedirectUrl($editorUrl, $requestReferrer, Draft $draftProcess, CustomerSession $customerSession, $storeId = 0, $params = [])
    {
        /**
         * Disassembly editor url into base url and params for following process
         */
        $editorUrlparts = explode('?', $editorUrl);

        $editorUrlBase = $editorUrlparts[0];
        $editorUrlParams = '';
        if(isset($editorUrlparts[1])) {
            $editorUrlParams = $editorUrlparts[1];
        }

        $editorUrlParamsArray = [];
        parse_str($editorUrlParams, $editorUrlParamsArray);

        /**
         * Get callback url and add it to params
         */
        $editorUrlParamsArray['callback'] = $this->_getCallbackUrl($requestReferrer, $draftProcess, $storeId, $params);

        /**
         * Add customer id to params
         */
        if($customerSession->isLoggedIn()) {
            $editorUrlParamsArray['user'] = $customerSession->getCustomerId();
        }

        $paramsObject = new DataObject($editorUrlParamsArray);
        $this->_eventManager->dispatch(
            'printformer_open_assign_url_params_before',
            [
                'request' => $this->_request,
                'params' => $paramsObject
            ]
        );
        /**
         * Override editor params with current action params
         */
        foreach($paramsObject->getData() as $key => $param) {
            $editorUrlParamsArray[$key] = $param;
        }

        /**
         * Assemble url with params and return it
         */
        $queryArray = [];
        foreach($editorUrlParamsArray as $key => $value) {
            $queryArray[] = $key . '=' . $value;
        }

        $editorUrl = $editorUrlBase . '?' . implode('&', $queryArray);

        return $editorUrl;
    }
}