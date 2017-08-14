<?php

namespace Rissc\Printformer\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Rissc\Printformer\Gateway\Admin\Product;
use Rissc\Printformer\Helper\Config;

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
     * Sync constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Product $gateway
     * @param Config $config
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Product $gateway,
        Config $config
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->gateway = $gateway;
        $this->config = $config;
        parent::__construct($context);
    }

    public function execute()
    {
        $storeId = $this->getRequest()->getParam('store_id', \Magento\Store\Model\Store::DEFAULT_STORE_ID);
        try {
            if (!$this->config->setStoreId($storeId)->isEnabled()) {
                throw new \Exception(__('Module disabled.'));
            }

            $this->gateway->syncProducts($storeId);
            $response = ['success' => 'true', 'message' => __('Products sync successful.')];
        } catch (\Exception $e) {
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
