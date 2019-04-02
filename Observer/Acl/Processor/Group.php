<?php

namespace Rissc\Printformer\Observer\Acl\Processor;

use Magento\Customer\Model\GroupFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Rissc\Printformer\Model\AclData;
use Rissc\Printformer\Helper\Customer\Group\Right as RightHelper;

class Group implements ObserverInterface
{
    /**
     * @var CustomerCollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @var RightHelper
     */
    protected $rightHelper;

    /**
     * @var GroupFactory
     */
    protected $groupFactory;

    /**
     * Group constructor.
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param GroupFactory $groupFactory
     * @param RightHelper $rightHelper
     */
    public function __construct(
        CustomerCollectionFactory $customerCollectionFactory,
        GroupFactory $groupFactory,
        RightHelper $rightHelper
    )
    {
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->groupFactory = $groupFactory;
        $this->rightHelper = $rightHelper;
    }

    /**
     * Check customer group right for action
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        /** @var AclData $aclData */
        $aclData = $observer->getAclData();

        $userIdentifier = $aclData->getUserIdentifier();

        $collection = $this->customerCollectionFactory->create();
        $collection->addFieldToFilter('printformer_identification', ['eq' => $userIdentifier]);

        if ($collection->count() == 1) {
            /** @var \Magento\Customer\Model\Customer $customer */
            $customer = $collection->getFirstItem();

            /** @var \Magento\Customer\Model\Data\Group $group */
            $group = $this->groupFactory->create()->load($customer->getGroupId());
            $right = $this->rightHelper->getRight($group);

            if ($right->hasRight($this->rightHelper->getRightKey($aclData))) {
                $aclData->setAllowAction(true);
            }
        }
    }
}