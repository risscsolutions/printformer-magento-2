<?php
namespace Rissc\Printformer\Controller\Adminhtml\Product;

class Sync extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Rissc\Printformer\Gateway\Admin\Product
     */
    protected $gateway;

    /**
     * @var \Rissc\Printformer\Helper\Config
     */
    protected $config;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Rissc\Printformer\Gateway\Admin\Product $gateway
     * @param \Rissc\Printformer\Helper\Config $config
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Rissc\Printformer\Gateway\Admin\Product $gateway,
        \Rissc\Printformer\Helper\Config $config
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->gateway = $gateway;
        $this->config = $config;
        parent::__construct($context);
    }

    /* (non-PHPdoc)
     * @see \Magento\Framework\App\ActionInterface::execute()
     */
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

    /* (non-PHPdoc)
     * @see \Magento\Backend\App\AbstractAction::_isAllowed()
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Rissc_Printformer::config');
    }
}
