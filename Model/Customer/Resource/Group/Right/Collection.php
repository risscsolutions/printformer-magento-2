<?php

namespace Rissc\Printformer\Model\Customer\Resource\Group\Right;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init(
            \Rissc\Printformer\Model\Customer\Group\Right::class,
            \Rissc\Printformer\Model\Customer\Resource\Group\Right::class
        );
    }
}