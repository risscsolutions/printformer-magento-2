<?php

namespace Rissc\Printformer\Model\Product;

class Source extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Rissc\Printformer\Model\ProductFactory
     */
    protected $printformerProductFactory;

    /**
     * @param   \Magento\Store\Model\StoreManagerInterface  $storeManager
     * @param   \Rissc\Printformer\Model\ProductFactory     $printformerProductFactory
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Rissc\Printformer\Model\ProductFactory $printformerProductFactory
    ) {
        $this->storeManager              = $storeManager;
        $this->printformerProductFactory = $printformerProductFactory;
    }

    /**
     * Retrieve all options array
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $products         = $this->printformerProductFactory
                ->create()->getCollection()
                ->addFieldToFilter('store_id', $this->getCurrentStoreId());
            $this->_options[] = array(
                'label' => __('-- Please Select --'),
                'value' => '',
            );
            foreach ($products as $product) {
                $this->_options[] = array(
                    'label' => $product->getName(),
                    'value' => $product->getMasterId(),
                );
            }
        }

        return $this->_options;
    }

    /**
     * @return int
     */
    protected function getCurrentStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }
}
