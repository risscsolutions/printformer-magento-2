<?php

namespace Rissc\Printformer\Model\Customer\Resource\Group;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Right extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('printformer_customer_group_right', 'id');
    }
}