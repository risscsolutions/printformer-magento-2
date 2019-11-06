<?php

namespace Rissc\Printformer\Plugin\Sales;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Rissc\Printformer\Setup\InstallSchema;

class OrderModel
{
    protected $cartRepository;
    protected $logger;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        LoggerInterface $logger
    ) {
        $this->cartRepository = $cartRepository;
        $this->logger = $logger;
    }

    /**
     * Set printformer data to order item
     *
     * @param Order $subject
     */
    public function beforePlace(Order $subject)
    {
        try {
            if(!$subject->getQuote()) {
                $quote = $this->cartRepository->get($subject->getQuoteId());
            } else {
                $quote = $subject->getQuote();
            }
            $allItems = $quote->getAllItems();
            if(count($allItems) > 0) {
                /** @var \Magento\Sales\Model\Order\Item $item */
                foreach ($subject->getAllItems() as $key => $item) {
                    /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
                    $quoteItem = $allItems[$key];
                    $draftId = $quoteItem->getData(InstallSchema::COLUMN_NAME_DRAFTID);
                    if ($draftId) {
                        $item->setData(InstallSchema::COLUMN_NAME_DRAFTID, $draftId);
                    }
                    $storeId = $quoteItem->getData(InstallSchema::COLUMN_NAME_STOREID);
                    if ($storeId) {
                        $item->setData(InstallSchema::COLUMN_NAME_STOREID, $storeId);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
