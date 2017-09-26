<?php

namespace Rissc\Printformer\Controller\Adminhtml\Product;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\Store;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute as EntityAttribute;
use Rissc\Printformer\Helper\Config as ConfigHelper;
use Magento\Backend\App\Action;

class Attribute
    extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Config
     */
    protected $eavConfig;

    /**
     * @var ConfigHelper
     */
    protected $config;

    /**
     * Attribute constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Config $eavConfig
     * @param ConfigHelper $config
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Config $eavConfig,
        ConfigHelper $config
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->eavConfig = $eavConfig;
        $this->config = $config;
        parent::__construct($context);
    }

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

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Rissc_Printformer::config');
    }
}
