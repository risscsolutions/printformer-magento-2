<?php

namespace Rissc\Printformer\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Rissc\Printformer\Setup\UpgradeSchema;

class Log
    extends AbstractDb
{
    protected function _construct()
    {
        $this->_init(UpgradeSchema::TABLE_NAME_HISTORY, 'id');
    }
}
