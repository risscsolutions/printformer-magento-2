<?php

namespace Rissc\Printformer\Controller\Adminhtml\Group;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Model\ResourceModel\Group\Collection as GroupCollection;
use Magento\Customer\Api\GroupRepositoryInterface;
use Rissc\Printformer\Api\Data\Customer\Group\RightInterface;
use Rissc\Printformer\Model\Customer\Group\RightRepository;
use Rissc\Printformer\Model\Customer\Group\RightFactory;
use Rissc\Printformer\Helper\Customer\Group\Right as RightHelper;

class Save extends Action
{
    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * @var RightRepository
     */
    protected $rightRepository;

    /**
     * @var GroupCollection
     */
    protected $groupCollection;

    protected $groupRepository;

    /**
     * @var RightFactory
     */
    protected $rightFactory;

    /**
     * @var RightHelper
     */
    protected $rightHelper;

    protected $group = [];

    protected $rights = [
        RightInterface::DRAFT_EDITOR_VIEW,
        RightInterface::DRAFT_EDITOR_UPDATE,
        RightInterface::REVIEW_VIEW,
        RightInterface::REVIEW_END,
        RightInterface::REVIEW_FINISH
    ];

    /**
     * Save constructor.
     * @param PageFactory $pageFactory
     * @param GroupCollection $groupCollection
     * @param RightRepository $rightRepository
     * @param RightFactory $rightFactory
     * @param Context $context
     */
    public function __construct(
        PageFactory $pageFactory,
        GroupRepositoryInterface $groupRepository,
        GroupCollection $groupCollection,
        RightRepository $rightRepository,
        RightFactory $rightFactory,
        RightHelper $rightHelper,
        Context $context
    ) {
        $this->pageFactory = $pageFactory;
        $this->groupRepository = $groupRepository;
        $this->groupCollection = $groupCollection;
        $this->rightRepository = $rightRepository;
        $this->rightFactory = $rightFactory;
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