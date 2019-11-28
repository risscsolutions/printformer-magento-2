<?php

namespace Rissc\Printformer\Model\Catalog\Printformer;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;

class Product extends AbstractModel implements IdentityInterface
{
    const CACHE_TAG = 'catalog_product_printformer_product';

    protected function _construct()
    {
        $this->_init(\Rissc\Printformer\Model\ResourceModel\Catalog\Printformer\Product::class);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}