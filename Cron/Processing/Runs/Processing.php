<?php
namespace Rissc\Printformer\Cron\Processing\Runs;

use Psr\Log\LoggerInterface;
use Rissc\Printformer\Helper\Order;
use Rissc\Printformer\Helper\Api;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as ItemCollection;

/**
 * Class Processing
 * @package Rissc\Printformer\Cron\Processing\Runs
 */
abstract class Processing
{
    /**
     * Default format necessary to work with created_at db-field in magento-2
     * (most important is the 24-hour format – leading zeroes)
     */
    const DEFAULT_DB_FORMAT = "Y-m-d H:i:s";

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Order
     */
    protected $orderHelper;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var false|string
     */
    protected $toDateTime = null;

    /**
     * @var false|int
     */
    protected $fromDateTime = null;

    /**
     * Processing constructor.
     * @param LoggerInterface $logger
     * @param Order $orderHelper
     * @param Api $api
     */
    public function __construct(
        LoggerInterface $logger,
        Order $orderHelper,
        Api $api
    )
    {
        $this->logger = $logger;
        $this->orderHelper = $orderHelper;
        $this->api = $api;
    }

    /**
     * Run Cron
     */
    public function execute()
    {
        $this->logger->debug('--------------------------------Execution started--------------------------------');
        $this->resetProcessingFilters();
        $this->setFromToFilters();

        if (isset($this->fromDateTime, $this->toDateTime)){
            $this->logger->debug('--------------------------------with filters from:'.$this->fromDateTime.' to '.$this->toDateTime.'--------------------------------');
        }

        $unprocessedOrderItemsCollection = $this->getUnprocessedPrintformerOrderUploadItemsCollection();
        $this->uploadPrintformerOrderUploadItems($unprocessedOrderItemsCollection);

        $unprocessedPrintformerOrderItemDraftsCollection = $this->getUnprocessedPrintformerOrderItemsCollection();
        $draftIdsToProcess = $this->loadUnprocessedPrintformerOrderItemDrafts($unprocessedPrintformerOrderItemDraftsCollection);

        if (!empty($draftIdsToProcess)){
            foreach ($draftIdsToProcess as $draftId) {
                $draftToSync = [];
                array_push($draftToSync, $draftId);
                $this->api->setAsyncOrdered($draftToSync);
            }
        } else {
            $this->logger->debug('--------------------------------no items with draft to process found-------------------------------');
        }

        $this->logger->debug('--------------------------------Execution finished-------------------------------');
    }

    /**
     * @return boolean
     */
    abstract protected function setFromToFilters();

    /**
     * Get unprocessed Printformer-Upload-items with cron specific filter
     * @return ItemCollection
     */
    private function getUnprocessedPrintformerOrderUploadItemsCollection(){
        $collection = $this->orderHelper->getUnprocessedPrintformerOrderUploadItems();

        if (isset($this->toDateTime, $this->fromDateTime)){
            $collection->addFieldToFilter('main_table.created_at', array('from'=>$this->fromDateTime, 'to'=>$this->toDateTime));
        }

        return $collection;
    }

    /**
     * Get unprocessed Printformer-Order-items with cron specific filter
     * @return ItemCollection
     */
    protected function getUnprocessedPrintformerOrderItemsCollection(){
        $collection = $this->orderHelper->getUnprocessedPrintformerOrderItemDrafts();

        if (isset($this->toDateTime, $this->fromDateTime)) {
            $collection->addFieldToFilter('main_table.created_at', array('from' => $this->fromDateTime, 'to' => $this->toDateTime));
        }

        return $collection;
    }

    /**
     * @param $unprocessedPrintformerOrderItemDraftsCollection
     * @return array
     */
    private function loadUnprocessedPrintformerOrderItemDrafts($unprocessedPrintformerOrderItemDraftsCollection)
    {
        $unprocessedPrintformerOrderItemDrafts = [];
        if (isset($unprocessedPrintformerOrderItemDraftsCollection)){
            $unprocessedPrintformerOrderItemDrafts = $unprocessedPrintformerOrderItemDraftsCollection->getData();
            if (!empty($unprocessedPrintformerOrderItemDrafts)){
                $unprocessedPrintformerOrderItemDrafts = array_map('current', $unprocessedPrintformerOrderItemDrafts);
            }
        }

        return $unprocessedPrintformerOrderItemDrafts;
    }

    /**
     * @param $unprocessedOrderItemsCollection
     * @return array
     */
    private function uploadPrintformerOrderUploadItems($unprocessedOrderItemsCollection)
    {
        $uploadedItemsDrafts = [];
        if (isset($unprocessedOrderItemsCollection)){
            $unprocessedOrderItemsCollectionItems = $unprocessedOrderItemsCollection->getItems();

            foreach ($unprocessedOrderItemsCollectionItems as $unprocessedOrderItem) {
                $itemId = $unprocessedOrderItem->getitemId();
                $orderId = $unprocessedOrderItem->getOrderId();
                try {
                    $resultDraftHash = $this->orderHelper->loadPayLoadInformationByOrderIdAndUploadFile($orderId, $itemId);
                    if ($resultDraftHash) {
                        array_push($uploadedItemsDrafts, $resultDraftHash);
                    }
                } catch (\Exception $e) {
                    $this->logger->debug('Loading payload-information failed for item with item-id: '.$itemId.' and order-id'.$orderId);
                }
            }
        }
        return $uploadedItemsDrafts;
    }

    /**
     * Reset all previous used filters
     */
    private function resetProcessingFilters()
    {
        $this->toDateTime = null;
        $this->fromDateTime = null;
    }
}