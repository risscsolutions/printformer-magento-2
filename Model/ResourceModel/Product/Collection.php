<?php
namespace Rissc\Printformer\Model\ResourceModel\Product;

use Rissc\Printformer\Setup\InstallSchema;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /* (non-PHPdoc)
     * @see \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection::_construct()
     */
    protected function _construct()
    {
        $this->_init(
            'Rissc\Printformer\Model\Product',
            'Rissc\Printformer\Model\ResourceModel\Product'
        );
    }
}
