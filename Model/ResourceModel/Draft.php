<?php

namespace Rissc\Printformer\Model\ResourceModel;

use Rissc\Printformer\Setup\InstallSchema;

class Draft extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init(InstallSchema::TABLE_NAME_DRAFT, 'id');
    }
}
