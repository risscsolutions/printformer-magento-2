<?php

namespace Rissc\Printformer\Model;

use Magento\Framework\Model\AbstractModel;

class PrintformerUserGroup extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Rissc\Printformer\Model\ResourceModel\PrintformerUserGroup::class);
    }
}