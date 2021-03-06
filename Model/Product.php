<?php
namespace Rissc\Printformer\Model;

class Product extends \Magento\Framework\Model\AbstractModel
    implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'printformer_product';

    protected function _construct()
    {
        $this->_init('Rissc\Printformer\Model\ResourceModel\Product');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
