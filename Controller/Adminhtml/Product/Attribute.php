<?php
namespace Rissc\Printformer\Controller\Adminhtml\Product;

use \Magento\Store\Model\Store;
use \Magento\Eav\Model\Entity\Attribute as EntityAttribute;

class Attribute extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;

    /**
     * @var \Rissc\Printformer\Helper\Config
     */
    protected $config;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Rissc\Printformer\Helper\Config $config
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Rissc\Printformer\Helper\Config $config
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->eavConfig = $eavConfig;
        $this->config = $config;
        parent::__construct($context);
    }

    /* (non-PHPdoc)
     * @see \Magento\Framework\App\ActionInterface::execute()
     */
    public function execute()
    {
        $storeId = $this->getRequest()->getParam('store_id', Store::DEFAULT_STORE_ID);
        $attributeCode = $this->getRequest()->getParam('code', Store::DEFAULT_STORE_ID);
        try {
            if (!$this->config->setStoreId($storeId)->isEnabled()) {
                throw new \Exception(__('Module disabled.'));
            }

            $attribute = $this->eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, $attributeCode);
            if (!($attribute instanceOf EntityAttribute\AbstractAttribute)) {
                throw new \Exception(__('Attribute not found.'));
            }

            $source = $attribute->getSource();
            if (!($source instanceOf EntityAttribute\Source\AbstractSource)) {
                throw new \Exception(__('Invalid attribute.'));
            }

            $data = [];
            foreach ($source->getAllOptions() as $option) {
                if (empty($option['value'])) {
                    continue;
                }
                $data[$option['value']] = $option['label'];
            }

            $response = ['success' => 'true', 'data' => $data];
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
