<?php
namespace Rissc\Printformer\Plugin\Customer\Section\Load;

use Magento\Customer\Controller\Section\Load;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Rissc\Printformer\Helper\Api as ApiHelper;
use Rissc\Printformer\Helper\Session as SessionHelper;
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
    private SessionHelper $sessionHelper;

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
        SessionHelper $sessionHelper
    ) {
        $this->_customerSession = $customerSession;
        $this->_quoteFactory = $quoteFactory;
        $this->_quoteResource = $quoteResource;
        $this->_draftResource = $draftResource;
        $this->_apiHelper = $apiHelper;
        $this->sessionHelper = $sessionHelper;
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
                $draftFromBuyRequest = $item->getPrintformerDraftid();
                if (!$draftFromBuyRequest) {
                    continue;
                }

                if (!empty($draftFromBuyRequest)) {
                    $draftIds = explode(',', $draftFromBuyRequest);

                    $foreachCount = 0;

                    $userIdentifier = $this->_apiHelper->getUserIdentifier();
                    if ($userIdentifier != $customer->getPrintformerIdentification()) {
                        $userIdentifier = $customer->getPrintformerIdentification();
                    }
                    $productId = $item->getProduct()->getId();

                    $resultDraftIds = [];
                    foreach ($draftIds as $draftId) {
                        /** @var Draft $draftProcess */
                        $draftProcess = $this->_apiHelper->draftProcess($draftId);
                        if (!$draftProcess->getId()) {
                            $resultDraftIds[] = $draftId;
                            continue;
                        }

                        if (!$draftProcess->getCustomerId()) {
                            $replicateDraft = $this->_apiHelper->generateNewReplicateDraft($draftProcess->getDraftId(), $customer->getId(), $userIdentifier);
                            $newDraftId = $replicateDraft->getDraftId();
                            $pfProductId = $replicateDraft->getPrintformerProductId();
                            $resultDraftIds[] = $newDraftId;

                            if ($foreachCount >= 0) {
                                $this->sessionHelper->removeSessionUniqueIdFromSession($productId, $pfProductId);
                                $foreachCount++;
                            }
                            $this->sessionHelper->loadSessionUniqueId($productId, $pfProductId, $newDraftId);
                        } else {
                            $resultDraftIds[] = $draftId;
                        }
                    }

                    if (!empty($resultDraftIds)) {
                        $draftIdField = implode(',', $resultDraftIds);
                        $item->setPrintformerDraftid($draftIdField);
                        $item->save();
                    }
                }
            }
        }

        return $returnValue;
    }
}