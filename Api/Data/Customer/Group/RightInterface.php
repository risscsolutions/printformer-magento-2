<?php

namespace Rissc\Printformer\Api\Data\Customer\Group;

interface RightInterface
{
    const ID = 'id';
    const CUSTOMER_GROUP_ID = 'customer_group_id';
    const DRAFT_EDITOR_VIEW = 'draft_editor_view';
    const DRAFT_EDITOR_UPDATE = 'draft_editor_update';
    const REVIEW_VIEW = 'review_view';
    const REVIEW_FINISH = 'review_finish';
    const REVIEW_END = 'review_end';

    /**
     * @return int
     */
    public function getId();

    /**
     * @return int
     */
    public function getCustomerGroupId();

    /**
     * @return bool
     */
    public function getDraftEditorView();

    /**
     * @return bool
     */
    public function getDraftEditorUpdate();

    /**
     * @return bool
     */
    public function getReviewView();

    /**
     * @return bool
     */
    public function getReviewFinish();

    /**
     * @return bool
     */
    public function getReviewEnd();

    /**
     * @param int $id
     * @return \Rissc\Printformer\Api\Data\Customer\Group\RightInterface
     */
    public function setId($id);

    /**
     * @param int $customerGroupId
     * @return \Rissc\Printformer\Api\Data\Customer\Group\RightInterface
     */
    public function setCustomerGroupId($customerGroupId);

    /**
     * @param bool $draftEditorView
     * @return \Rissc\Printformer\Api\Data\Customer\Group\RightInterface
     */
    public function setDraftEditorView($draftEditorView);

    /**
     * @param bool $draftEditorUpdate
     * @return \Rissc\Printformer\Api\Data\Customer\Group\RightInterface
     */
    public function setDraftEditorUpdate($draftEditorUpdate);

    /**
     * @param bool $reviewView
     * @return \Rissc\Printformer\Api\Data\Customer\Group\RightInterface
     */
    public function setReviewView($reviewView);

    /**
     * @param bool $reviewFinish
     * @return \Rissc\Printformer\Api\Data\Customer\Group\RightInterface
     */
    public function setReviewFinish($reviewFinish);

    /**
     * @param bool $reviewEnd
     * @return \Rissc\Printformer\Api\Data\Customer\Group\RightInterface
     */
    public function setReviewEnd($reviewEnd);

    /**
     * @param string $key
     * @param bool $value
     * @return \Rissc\Printformer\Api\Data\Customer\Group\RightInterface
     */
    public function setRightValue($key, $value);

    /**
     * @return \Rissc\Printformer\Api\Data\Customer\Group\RightInterface
     */
    public function reset();

    /**
     * @param string $key
     * @return bool
     */
    public function hasRight($key);
}