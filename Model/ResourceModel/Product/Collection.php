<?php

namespace Rissc\Printformer\Model\ResourceModel\Product;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init(
            'Rissc\Printformer\Model\Product',
            'Rissc\Printformer\Model\ResourceModel\Product'
        );
    }
}
