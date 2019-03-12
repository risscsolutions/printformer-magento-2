<?php

namespace Rissc\Printformer\Observer\Acl\Processor;

use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Rissc\Printformer\Model\AclData;
use Mgo\WebService\Model\ExternalUserFactory;
use Mgo\WebService\Model\ResourceModel\ExternalUser as ExternalUserResource;
use Mgo\WebService\Model\ExternalUser;
use Mgo\WebService\Model\ResourceModel\ExternalUser\CollectionFactory as ExternalUserCollectionFactory;
use Magento\Customer\Model\GroupFactory;
use Rissc\Printformer\Helper\Customer\Group\Right as RightHelper;

class Customer implements ObserverInterface
{
    /** @var CustomerCollection */
    protected $customerCollection;

    /** @var ExternalUserFactory */
    protected $_externalUserFactory;

    /** @var ExternalUserResource */
    protected $_externalUserResource;

    /** @var ExternalUserCollectionFactory */
    protected $_externalUserCollectionFactory;

    /** @var RightHelper */
    protected $rightHelper;

    /** @var GroupFactory */
    protected $groupFactory;

    /**
     * Customer constructor.
     *
     * @param CustomerCollection $customerCollection
     * @param ExternalUserFactory $externalUserFactory
     * @param ExternalUserResource $externalUserResource
     * @param ExternalUserCollectionFactory $externalUserCollectionFactory
     * @param GroupFactory $groupFactory
     * @param RightHelper $rightHelper
     */
    public function __construct(
        CustomerCollection $customerCollection,
        ExternalUserFactory $externalUserFactory,
        ExternalUserResource $externalUserResource,
        ExternalUserCollectionFactory $externalUserCollectionFactory,
        GroupFactory $groupFactory,
        RightHelper $rightHelper
    ) {
        $this->customerCollection = $customerCollection;
        $this->groupFactory = $groupFactory;
        $this->rightHelper = $rightHelper;
        $this->_externalUserFactory = $externalUserFactory;
        $this->_externalUserResource = $externalUserResource;
        $this->_externalUserCollectionFactory = $externalUserCollectionFactory;
    }

    /**
     * Check customer right for action
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        /** @var AclData $aclData */
        $aclData = $observer->getAclData();

        $userIdentifier = $aclData->getUserIdentifier();

        // get external user table and load customer by identifier from this observer

        $collection = $this->_externalUserCollectionFactory->create();
        $collection->addFieldToFilter('printformer_identifier', ['eq' => $userIdentifier]);

        if ($collection->count() == 1) {

            $customer = $collection->getFirstItem();

            /** @var \Magento\Customer\Model\Data\Group $group */
            $group = $this->groupFactory->create()->load($customer->getExternalOnetimeUserCategoryId());
            $right = $this->rightHelper->getRight($group);

            if ($right->hasRight($this->rightHelper->getRightKey($aclData))) {
                $aclData->setAllowAction(true);
            }
        }
    }
}