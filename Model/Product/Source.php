<?php
namespace Rissc\Printformer\Model\Product;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Store\Model\StoreManagerInterface;
use Rissc\Printformer\Model\ProductFactory;

class Source extends AbstractSource
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductFactory
     */
    protected $printformerProductFactory;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ProductFactory $printformerProductFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ProductFactory $printformerProductFactory
    ) {
        $this->storeManager = $storeManager;
        $this->printformerProductFactory = $printformerProductFactory;
    }

    /**
     * @return int
     */
    protected function getCurrentStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * Retrieve all options array
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $products = $this->printformerProductFactory
                ->create()->getCollection()
                ->addFieldToFilter('store_id', $this->getCurrentStoreId());
            $this->_options[] = array(
                'label' => __('-- Please Select --'),
                'value' => '',
            );
            foreach ($products as $product) {
                $this->_options[] = array(
                    'label' => $product->getName(),
                    'value' => $product->getIdentifier(),
                );
            }
        }
        return $this->_options;
    }
}
