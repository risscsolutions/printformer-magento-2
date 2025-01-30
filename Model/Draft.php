<?php
namespace Rissc\Printformer\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class Draft extends AbstractModel implements IdentityInterface
{
    const CACHE_TAG = 'printformer_draft';

    const KEY_USER_IDENTIFIER = 'user_identifier';
    const KEY_DRAFT_HASH = 'draft_id';
    const KEY_DRAFT_ID = 'draft_id';
    const KEY_IDENTIFIER = 'identifier';
    const KEY_PRODUCT_ID = 'product_id';
    const KEY_PROCESSING_ID = 'processing_id';
    const KEY_PROCESSING_STATUS = 'processing_status';
    const KEY_USER_GROUP_IDENTIFIER = 'user_group_identifier';

    protected function _construct()
    {
        $this->_init('Rissc\Printformer\Model\ResourceModel\Draft');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
