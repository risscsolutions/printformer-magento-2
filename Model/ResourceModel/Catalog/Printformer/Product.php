<?php

namespace Rissc\Printformer\Model\ResourceModel\Catalog\Printformer;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Rissc\Printformer\Setup\UpgradeSchema;

class Product extends AbstractDb
{
    protected function _construct()
    {
        $this->_init(UpgradeSchema::TABLE_NAME_CATALOG_PRODUCT_PRINTFORMER_PRODUCT,
            'id');
    }
}
