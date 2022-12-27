<?php

namespace Rissc\Printformer\Observer\Product\Sync\After;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class Observer
    implements ObserverInterface
{
    /** @var ProductFactory */
    protected $_productFactory;

    public function __construct(
        ProductFactory $_productFactory
    ) {
        $this->_productFactory = $_productFactory;
    }

    public function execute(EventObserver $observer)
    {
        /** @var Product $product */
        $product           = $this->_productFactory->create();
        $productCollection = $product->getCollection()
            ->addAttributeToFilter('printformer_enabled', ['eq' => 1])
            ->addAttributeToFilter('printformer_product', ['neq' => 0]);

        /** @var Product $_product */
        foreach ($productCollection->getItems() as $_product) {
            $_product->setPrintformerEnabled(0);
            $_product->setPrintformerProduct(0);

            $_product->getResource()->save($_product);
        }
    }
}
