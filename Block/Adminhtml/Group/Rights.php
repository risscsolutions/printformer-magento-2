<?php

namespace Rissc\Printformer\Block\Adminhtml\Group;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Customer\Model\Group;
use Magento\Customer\Model\ResourceModel\Group\Collection as GroupCollection;
use Rissc\Printformer\Api\Data\Customer\Group\RightInterface;
use Rissc\Printformer\Helper\Customer\Group\Right as RightHelper;

class Rights extends Template
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
     * Rights constructor.
     * @param GroupCollection $groupCollection
     * @param RightHelper $rightHelper
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        GroupCollection $groupCollection,
        RightHelper $rightHelper,
        Context $context,
        array $data = []
    ) {
        $this->groupCollection = $groupCollection;
        $this->rightHelper = $rightHelper;
        parent::__construct($context, $data);
    }

    /**
     * @param Group $group
     * @return RightInterface
     */
    public function getRight(Group $group)
    {
        return $this->rightHelper->getRight($group);
    }

    /**
     * @return GroupCollection
     */
    public function getGroups()
    {
        return $this->groupCollection;
    }

    /**
     * @return string
     */
    public function getFormAction()
    {
        return $this->getUrl('*/*/save');
    }
}