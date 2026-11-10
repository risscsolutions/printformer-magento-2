<?php

declare(strict_types=1);

namespace Rissc\Printformer\Observer\Acl\Processor;

use Magento\Customer\Model\Group as CustomerGroup;
use Magento\Customer\Model\GroupFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Customer\Model\ResourceModel\Group as GroupResource;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Rissc\Printformer\Helper\Customer\Group\Right as RightHelper;
use Rissc\Printformer\Model\AclData;

/**
 * Allows an ACL action when the printformer customer's group has the required right.
 */
class Customer implements ObserverInterface
{
    public function __construct(
        protected readonly CustomerCollectionFactory $customerCollectionFactory,
        protected readonly GroupFactory $groupFactory,
        protected readonly GroupResource $groupResource,
        protected readonly RightHelper $rightHelper
    ) {
    }

    /**
     * Check the customer group right for the requested ACL action.
     */
    public function execute(EventObserver $observer): void
    {
        /** @var AclData $aclData */
        $aclData = $observer->getAclData();

        $groupId = $this->resolveCustomerGroupId((string)$aclData->getUserIdentifier());

        /** @var CustomerGroup $group */
        $group = $this->groupFactory->create();
        $this->groupResource->load($group, $groupId);

        $right = $this->rightHelper->getRight($group);

        if ($right->hasRight($this->rightHelper->getRightKey($aclData))) {
            $aclData->setAllowAction(true);
        }
    }

    /**
     * Resolve the customer group id for the printformer identifier.
     * Falls back to the "NOT LOGGED IN" group when no unique customer match is found.
     */
    private function resolveCustomerGroupId(string $userIdentifier): int
    {
        $collection = $this->customerCollectionFactory->create();
        $collection->addFieldToFilter('printformer_identification', ['eq' => $userIdentifier]);

        if ($collection->count() === 1) {
            return (int)$collection->getFirstItem()->getGroupId();
        }

        return CustomerGroup::NOT_LOGGED_IN_ID;
    }
}
