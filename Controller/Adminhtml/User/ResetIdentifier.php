<?php
namespace Rissc\Printformer\Controller\Adminhtml\User;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Rissc\Printformer\Gateway\Admin\PrintformerIdentifier;
use Rissc\Printformer\Helper\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ResetIdentifier extends Action
{

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var PrintformerIdentifier
     */
    protected $_gateway;

    /**
     * Sync constructor.
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param PrintformerIdentifier $gateway
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        JsonFactory $resultJsonFactory,
        Config $config,
        PrintformerIdentifier $gateway
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->config = $config;
        $this->_scopeConfig = $scopeConfig;
        $this->_gateway = $gateway;
        parent::__construct($context);
    }

    public function execute()
    {
        $storeId = $this->getRequest()->getParam('store_id', \Magento\Store\Model\Store::DEFAULT_STORE_ID);
        $websiteId = $this->getRequest()->getParam('website_id', null);
        $response = [];

        try {
            if (!$this->config->isEnabled()) {
                throw new \Exception(__('Module disabled.'));
            }

            if ($websiteId !== null) {
                if ($this->_gateway->deletePrintformerIdentification($websiteId)) {
                    $response = ['success' => true, 'message' => __('Printformer Identifiers have been removed for all Stores from this Website.')];
                } else {
                    $response = ['success' => false, 'message' => __('Can\'t reset printformer_identification for %1.', $websiteId)];
                }
            }

            if ($storeId != "0") {
                if ($this->_gateway->deletePrintformerIdentificationByStoreId($storeId)) {
                    $response = ['success' => true, 'message' => __('Printformer Identifiers have been removed for Store with ID %1', $storeId)];
                } else {
                    $response = ['success' => false, 'message' => __('Can\'t reset printformer_identification for %1.', $storeId)];
                }
            }

            if (($storeId === 0 || $storeId == 0) && $websiteId === null) {
                if ($this->_gateway->deletePrintformerIdentification(null)) {
                    $response = ['success' => true, 'message' => __('All Printformer Identifiers have been removed')];
                } else {
                    $response = ['success' => false, 'message' => __('Can\'t reset printformer_identification.')];
                }
            }

        } catch (\Exception $e) {
            $response = ['success' => false, 'message' => $e->getMessage()];
        }

        $this->_actionFlag->set('', self::FLAG_NO_POST_DISPATCH, true);
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Rissc_Printformer::config');
    }
}