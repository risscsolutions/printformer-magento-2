<?php

namespace Rissc\Printformer\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Rissc\Printformer\Gateway\Admin\Product;
use Rissc\Printformer\Helper\Config;
use Rissc\Printformer\Helper\Api;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Sync extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Product
     */
    protected $gateway;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;
    private Api $apiHelper;

    /**
     * Sync constructor.
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param JsonFactory $resultJsonFactory
     * @param Product $gateway
     * @param Config $config
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        JsonFactory $resultJsonFactory,
        Product $gateway,
        Config $config,
        Api $apiHelper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->gateway = $gateway;
        $this->config = $config;
        $this->_scopeConfig = $scopeConfig;
        $this->apiHelper = $apiHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        $storeId = $this->getRequest()->getParam('store_id', false);
        $websiteId = $this->getRequest()->getParam('website_id', false);

        try {
            if (!$this->config->isEnabled($storeId, $websiteId)) {
                throw new \Exception(__('Module disabled.'));
            }

            try {
                $url = $this->apiHelper->apiUrl()->getClientName($storeId, $websiteId);
                $httpClient = $this->apiHelper->getHttpClient($storeId, $websiteId);

                $response = $httpClient->get($url);
                $response = json_decode($response->getBody(), true);
                $name = $response['data']['name'];

                $this->gateway->syncProducts($storeId, $websiteId);
                $response = ['success' => 'true', 'message' => __('Templates synchronized successfully.').'<br>'.__('Mandator:').$name];
            } catch (\Exception $e) {
                $response = ['error' => 'true', 'message' => __('No templates are assigned to Mandator Name.')];
            }
        } catch (\Exception $e){
            $response = ['error' => 'true', 'message' => $e->getMessage()];
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
