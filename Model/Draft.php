<?php
namespace Rissc\Printformer\Model;

class Draft extends \Magento\Framework\Model\AbstractModel
    implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'printformer_draft';

    protected function _construct()
    {
        $this->_init('Rissc\Printformer\Model\ResourceModel\Draft');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
