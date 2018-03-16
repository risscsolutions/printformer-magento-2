<?php

namespace Rissc\Printformer\Observer\Acl\Processor;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Rissc\Printformer\Model\AclData;

class Group implements ObserverInterface
{
    /**
     * Check customer group right for action
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        /** @var AclData $aclData */
        $aclData = $observer->getAclData();
        //$aclData->setAllowAction(true);
    }
}