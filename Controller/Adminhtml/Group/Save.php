<?php

namespace Rissc\Printformer\Controller\Adminhtml\Group;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Customer\Model\ResourceModel\Group\Collection as GroupCollection;
use Rissc\Printformer\Api\Data\Customer\Group\RightInterface;
use Rissc\Printformer\Helper\Customer\Group\Right as RightHelper;

class Save extends Action
{
    /**
     * @var GroupCollection
     */
    protected $groupCollection;

    /**
     * @var RightHelper
     */
    protected $rightHelper;

    /**
     * @var array
     */
    protected $group = [];

    /**
     * @var array
     */
    protected $rights = [
        RightInterface::DRAFT_EDITOR_VIEW,
        RightInterface::DRAFT_EDITOR_UPDATE,
        RightInterface::REVIEW_VIEW,
        RightInterface::REVIEW_END,
        RightInterface::REVIEW_FINISH
    ];

    /**
     * Save constructor.
     * @param GroupCollection $groupCollection
     * @param RightHelper $rightHelper
     * @param Context $context
     */
    public function __construct(
        GroupCollection $groupCollection,
        RightHelper $rightHelper,
        Context $context
    ) {
        $this->groupCollection = $groupCollection;
        $this->rightHelper = $rightHelper;
        parent::__construct($context);
    }

    /**
     * @param int $groupId
     * @return \Magento\Customer\Model\Group
     */
    protected function getGroup($groupId)
    {
        if(!isset($this->group[$groupId])) {
            $this->group[$groupId] = $this->groupCollection->getItemById($groupId);
        }
        return $this->group[$groupId];
    }

    /**
     * Save printformer customer rights
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create()->setPath('*/*/rights');

        $post = $this->getRequest()->getParams();

        $this->rightHelper->resetRights();

        foreach($this->rights as $key) {
            if(isset($post[$key])) {
                foreach($post[$key] as $groupId) {
                    $group = $this->getGroup($groupId);
                    $right = $this->rightHelper->getRight($group);
                    $right->setCustomerGroupId($group->getCustomerGroupId());
                    $right->setRightValue($key, true);
                }
            }
        }

        try {
            $this->rightHelper->saveRights();
            $this->messageManager->addSuccessMessage(__('Group rights have been updated successfully.'));
        } catch(CouldNotSaveException $ex) {
            $this->messageManager->addExceptionMessage($ex);
        }

        return $resultRedirect;
    }
}