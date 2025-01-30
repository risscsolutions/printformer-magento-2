<?php
namespace Rissc\Printformer\Model\ResourceModel\PrintformerUserGroup;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection
    extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Rissc\Printformer\Model\PrintformerUserGroup::class,
            \Rissc\Printformer\Model\ResourceModel\PrintformerUserGroup::class
        );
    }
}