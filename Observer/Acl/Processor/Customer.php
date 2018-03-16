<?php

namespace Rissc\Printformer\Observer\Acl\Processor;

use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Rissc\Printformer\Model\AclData;

class Customer implements ObserverInterface
{
    /**
     * @var CustomerCollection
     */
    protected $customerCollection;

    /**
     * Customer constructor.
     * @param CustomerCollection $customerCollection
     */
    public function __construct(
        CustomerCollection $customerCollection
    ) {
        $this->customerCollection = $customerCollection;
    }

    /**
     * Check customer right for action
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        /** @var AclData $aclData */
        $aclData = $observer->getAclData();

        $collection = $this->customerCollection
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('printformer_identification', $aclData->getUserIdentifier())
            ->load();

        if($collection->count() == 1) {
            /** @var \Magento\Customer\Model\Customer $customer */
            $customer = $collection->getFirstItem();

            $aclData->setAllowAction(true);
        }
    }
}