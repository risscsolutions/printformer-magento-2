<?php

namespace Rissc\Printformer\Plugin\Sales;

use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Rissc\Printformer\Gateway\Admin\Draft;
use Rissc\Printformer\Setup\InstallSchema;
use Rissc\Printformer\Helper\Config;
use Rissc\Printformer\Helper\Api as ApiHelper;
use Rissc\Printformer\Model\DraftFactory;
use Magento\Quote\Api\CartRepositoryInterface;

class OrderModel
{
    /**
     * @var ApiHelper
     */
    protected $apiHelper;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var Draft
     */
    protected $draft;

    /**
     * @var DraftFactory
     */
    protected $draftFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        ApiHelper $apiHelper,
        Config $config,
        CartRepositoryInterface $cartRepository,
        Draft $draft,
        DraftFactory $draftFactory,
        LoggerInterface $logger
    ) {
        $this->apiHelper = $apiHelper;
        $this->config = $config;
        $this->cartRepository = $cartRepository;
        $this->draftFactory = $draftFactory;
        $this->draft = $draft;
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

    /**
     * Check status for payed order
     *
     * @param Order $subject
     * @return Order
     */
    public function afterPlace(Order $subject)
    {
        try {
            $draftIds = [];
            foreach ($subject->getAllItems() as $item) {
                if ($item->getPrintformerOrdered() || !$item->getPrintformerDraftid()) {
                    continue;
                }

                $itemDraftIds = explode(',', $item->getData(InstallSchema::COLUMN_NAME_DRAFTID));
                foreach ($itemDraftIds as $draftId) {
                    $this->draftFactory
                        ->create()
                        ->load($draftId, 'draft_id')
                        ->setOrderItemId($item->getId())
                        ->save();

                    $draftIds[] = $draftId;
                }
            }

            if ($subject->getStatus() == $this->config->getOrderStatus()) {
                if ($this->config->getProcessingType() == Draft::DRAFT_PROCESSING_TYPE_SYNC && !$this->config->isV2Enabled()) {
                    $this->draft->setDraftOrdered($subject);
                } else {
                    $this->apiHelper->setAsyncOrdered($draftIds);
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return $subject;
    }
}
