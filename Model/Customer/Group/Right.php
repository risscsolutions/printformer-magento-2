<?php

namespace Rissc\Printformer\Model\Customer\Group;

use Magento\Framework\Model\AbstractModel;
use Rissc\Printformer\Api\Data\Customer\Group\RightInterface;

class Right extends AbstractModel implements RightInterface
{
    protected $_eventPrefix = 'printformer_customer_group_right';

    protected function _construct()
    {
        $this->_init(\Rissc\Printformer\Model\Customer\Resource\Group\Right::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return parent::getData(self::ID);
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerGroupId()
    {
        return parent::getData(self::CUSTOMER_GROUP_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function getDraftEditorView()
    {
        return parent::getData(self::DRAFT_EDITOR_VIEW);
    }

    /**
     * {@inheritdoc}
     */
    public function getDraftEditorUpdate()
    {
        return parent::getData(self::DRAFT_EDITOR_UPDATE);
    }

    /**
     * {@inheritdoc}
     */
    public function getReviewView()
    {
        return parent::getData(self::REVIEW_VIEW);
    }

    /**
     * {@inheritdoc}
     */
    public function getReviewFinish()
    {
        return parent::getData(self::REVIEW_FINISH);
    }

    /**
     * {@inheritdoc}
     */
    public function getReviewEnd()
    {
        return parent::getData(self::REVIEW_END);
    }

    /**
     * {@inheritdoc}
     */
    public function getSuperUser()
    {
        return parent::getData(self::SUPER_USER);
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function setCustomerGroupId($customerGroupId)
    {
        return $this->setData(self::CUSTOMER_GROUP_ID, $customerGroupId);
    }

    /**
     * {@inheritdoc}
     */
    public function setDraftEditorView($draftEditorView)
    {
        return $this->setData(self::DRAFT_EDITOR_VIEW, $draftEditorView);
    }

    /**
     * {@inheritdoc}
     */
    public function setDraftEditorUpdate($draftEditorUpdate)
    {
        return $this->setData(self::DRAFT_EDITOR_UPDATE, $draftEditorUpdate);
    }

    /**
     * {@inheritdoc}
     */
    public function setReviewView($reviewView)
    {
        return $this->setData(self::REVIEW_VIEW, $reviewView);
    }

    /**
     * {@inheritdoc}
     */
    public function setReviewFinish($reviewFinish)
    {
        return $this->setData(self::REVIEW_FINISH, $reviewFinish);
    }

    /**
     * {@inheritdoc}
     */
    public function setReviewEnd($reviewEnd)
    {
        return $this->setData(self::REVIEW_END, $reviewEnd);
    }

    /**
     * {@inheritdoc}
     */
    public function setSuperUser($superUser)
    {
        return $this->setData(self::SUPER_USER, $superUser);
    }

    /**
     * {@inheritdoc}
     */
    public function setRightValue($key, $value)
    {
        return $this->setData($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->setDraftEditorView(false);
        $this->setDraftEditorUpdate(false);
        $this->setReviewEnd(false);
        $this->setReviewFinish(false);
        $this->setReviewView(false);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasRight($key)
    {
        return (bool)$this->getData($key);
    }
}
