<?php
namespace Rissc\Printformer\Model\ResourceModel;

use Rissc\Printformer\Setup\InstallSchema;

class Draft extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /* (non-PHPdoc)
     * @see \Magento\Framework\Model\ResourceModel\AbstractResource::_construct()
     */
    protected function _construct()
    {
        $this->_init(InstallSchema::TABLE_NAME_DRAFT, 'id');
    }
}
