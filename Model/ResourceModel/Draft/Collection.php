<?php

namespace Rissc\Printformer\Model\ResourceModel\Draft;

class Collection extends
    \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init(
            'Rissc\Printformer\Model\Draft',
            'Rissc\Printformer\Model\ResourceModel\Draft'
        );
    }
}
