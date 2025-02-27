<?php
namespace Rissc\Printformer\Plugin\Customer\Section\Load;

use Magento\Customer\Controller\Section\Load;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\Store\Model\StoreManager;
use Rissc\Printformer\Helper\Api as ApiHelper;
use Rissc\Printformer\Helper\Config;
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

    /** @var Config */
    private Config $printformerConfig;

    /** @var StoreManager */
    private StoreManager $storeManager;

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
        ApiHelper $apiHelper,
        Config $printformerConfig,
        StoreManager $storeManager
    ) {
        $this->_customerSession = $customerSession;
        $this->_quoteFactory = $quoteFactory;
        $this->_quoteResource = $quoteResource;
        $this->_draftResource = $draftResource;
        $this->_apiHelper = $apiHelper;
        $this->printformerConfig = $printformerConfig;
        $this->storeManager = $storeManager;
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
        $storeId = $this->storeManager->getStore()->getId();
        if ($this->printformerConfig->isEnabled($storeId) && $this->_customerSession->isLoggedIn()) {
            /** @var Customer $customer */
            $customer = $this->_customerSession->getCustomer();

            /** @var Quote $quote */
            $quote = $this->_quoteFactory->create();
            $this->_quoteResource->loadByCustomerId($quote, $customer->getId());

            $guestUserIdentifiers = [];
            /** @var Quote\Item $item */
            foreach ($quote->getAllItems() as $item) {
                $draftFromBuyRequest = $item->getPrintformerDraftid();
                if (!$draftFromBuyRequest) {
                    continue;
                }

                if (!empty($draftFromBuyRequest)) {
                    $loggedUserIdentifier = $this->_apiHelper->getUserIdentifier();
                    if ($loggedUserIdentifier != $customer->getPrintformerIdentification()) {
                        $loggedUserIdentifier = $customer->getPrintformerIdentification();
                    }

                    $draftIds = explode(',', $draftFromBuyRequest);
                    foreach ($draftIds as $draftId) {
                        /** @var Draft $draftProcess */
                        $draftProcess = $this->_apiHelper->draftProcess($draftId);
                        if (!$draftProcess->getId()) {
                            continue;
                        }

                        $userIdentifierUsedInDraft = $draftProcess->getUserIdentifier();
                        if (!$draftProcess->getCustomerId() && $userIdentifierUsedInDraft !== $loggedUserIdentifier) {
                            if (!in_array($userIdentifierUsedInDraft, $guestUserIdentifiers)) {
                                $this->_apiHelper->mergeUsers($loggedUserIdentifier, $userIdentifierUsedInDraft);
                                $guestUserIdentifiers[] = $userIdentifierUsedInDraft;
                            }
                            $draftProcess->setData('user_identifier', $loggedUserIdentifier);
                            $draftProcess->save();
                        }
                    }
                }
            }
        }

        return $returnValue;
    }
}