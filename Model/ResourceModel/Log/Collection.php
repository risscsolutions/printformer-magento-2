<?php
namespace Rissc\Printformer\Model\ResourceModel\Log;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection
    extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'Rissc\Printformer\Model\History\Log',
            'Rissc\Printformer\Model\ResourceModel\Log'
        );
    }
}