<?php
namespace Rissc\Printformer\Plugin\Customer\Section\Load;

use Magento\Customer\Controller\Section\Load;
use Magento\Framework\DataObject;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Rissc\Printformer\Helper\Api as ApiHelper;
use Rissc\Printformer\Model\Draft;
use Rissc\Printformer\Model\ResourceModel\Draft as DraftResource;

/**
 * Class Plugin
 * @package Rissc\Printformer\Plugin\Customer\Section\Load
 */
class Plugin
{
    /** @var CustomerSession */
    protected $_customerSession;

    /** @var QuoteFactory */
    protected $_quoteFactory;

    /** @var QuoteResource */
    protected $_quoteResource;

    /** @var ApiHelper  */
    protected $_apiHelper;

    /** @var DraftResource */
    protected $_draftResource;

    /**
     * Plugin constructor.
     * 
     * @param CustomerSession $customerSession
     * @param QuoteFactory $quoteFactory
     * @param QuoteResource $quoteResource
     * @param DraftResource $draftResource
     * @param ApiHelper $apiHelper
     */
    public function __construct(
        CustomerSession $customerSession,
        QuoteFactory $quoteFactory,
        QuoteResource $quoteResource,
        DraftResource $draftResource,
        ApiHelper $apiHelper
    ) {
        $this->_customerSession = $customerSession;
        $this->_quoteFactory = $quoteFactory;
        $this->_quoteResource = $quoteResource;
        $this->_draftResource = $draftResource;
        $this->_apiHelper = $apiHelper;
    }

    /**
     * @param GuestCartManagement $subject
     * @param \Closure $oAssignCustomer
     *
     * @return boolean
     */
    public function aroundExecute(
        Load $subject,
        \Closure $oExecute
    ) {
        $returnValue = $oExecute();

        if ($this->_customerSession->isLoggedIn()) {
            /** @var Customer $customer */
            $customer = $this->_customerSession->getCustomer();

            /** @var Quote $quote */
            $quote = $this->_quoteFactory->create();
            $this->_quoteResource->loadByCustomerId($quote, $customer->getId());

            /** @var Quote\Item $item */
            foreach ($quote->getAllItems() as $item) {
                /** @var DataObject $buyRequest */
                $buyRequest = $item->getBuyRequest();

                $draftFromBuyRequest = $buyRequest->getPrintformerDraftid();
                if (!$draftFromBuyRequest) {
                    continue;
                }

                $draftIds = explode(',', $draftFromBuyRequest ?? '');

                if (!$draftIds) {
                    $draftIds = [];
                    array_push($draftIds, $draftFromBuyRequest);
                }

                foreach ($draftIds as $draftId) {
                    /** @var Draft $draftProcess */
                    $draftProcess = $this->_apiHelper->draftProcess($draftId);
                    if (!$draftProcess->getId()) {
                        continue;
                    }

                    $userIdentifier = $this->_apiHelper->getUserIdentifier();
                    if ($userIdentifier != $customer->getPrintformerIdentification()) {
                        $userIdentifier = $customer->getPrintformerIdentification();
                    }

                    if (!$draftProcess->getCustomerId()) {
                        $draftProcess->setCustomerId($customer->getId());
                        $draftProcess->setUserIdentifier($userIdentifier);

                        $this->_draftResource->save($draftProcess);
                    }
                }
            }
        }

        return $returnValue;
    }
}