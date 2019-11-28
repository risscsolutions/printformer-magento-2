<?php

namespace Rissc\Printformer\Model\ResourceModel\Catalog\Printformer\Product;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Rissc\Printformer\Model\Catalog\Printformer\Product::class,
            \Rissc\Printformer\Model\ResourceModel\Catalog\Printformer\Product::class
        );
    }
}
