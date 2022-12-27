<?php

namespace Rissc\Printformer\Model;

class Draft extends \Magento\Framework\Model\AbstractModel
    implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'printformer_draft';

    const KEY_USER_IDENTIFIER = 'user_identifier';
    const KEY_DRAFT_HASH = 'draft_id';
    const KEY_DRAFT_ID = 'draft_id';
    const KEY_MASTER_ID = 'master_id';
    const KEY_PRODUCT_ID = 'product_id';
    const KEY_PROCESSING_ID = 'processing_id';
    const KEY_PROCESSING_STATUS = 'processing_status';

    public function getIdentities()
    {
        return [self::CACHE_TAG.'_'.$this->getId()];
    }

    protected function _construct()
    {
        $this->_init('Rissc\Printformer\Model\ResourceModel\Draft');
    }
}
