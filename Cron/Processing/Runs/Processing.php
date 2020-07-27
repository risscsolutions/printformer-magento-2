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
        $this->logger->notice('--------------------------------Execution started--------------------------------');
        $this->resetProcessingFilters();
        $this->setFromToFilters();

        if (isset($this->fromDateTime, $this->toDateTime)){
            $this->logger->notice('--------------------------------with filters from:'.$this->fromDateTime.' to '.$this->toDateTime.'--------------------------------');
        }

        $unprocessedOrderItemsCollection = $this->getUnprocessedPrintformerOrderUploadItemsCollection();
        $uploadedItemsDrafts = $this->loadUnprocessedPrintformerOrderUploadItems($unprocessedOrderItemsCollection);

        $unprocessedPrintformerOrderItemDraftsCollection = $this->getUnprocessedPrintformerOrderItemsCollection();
        $unprocessedPrintformerOrderItemDrafts = $this->loadUnprocessedPrintformerOrderItemDrafts($unprocessedPrintformerOrderItemDraftsCollection);

        $draftIdsToProcess = array_merge($uploadedItemsDrafts, $unprocessedPrintformerOrderItemDrafts);
        $draftIdsToProcess = array_unique($draftIdsToProcess);

        if (!empty($draftIdsToProcess)){
            $this->startProcessing($draftIdsToProcess);
        } else {
            $this->logger->notice('--------------------------------no items with draft to process found-------------------------------');
        }

        $this->logger->notice('--------------------------------Execution finished-------------------------------');
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
    private function loadUnprocessedPrintformerOrderUploadItems($unprocessedOrderItemsCollection)
    {
        $uploadedItemsDrafts = [];
        if (isset($unprocessedOrderItemsCollection)){
            $unprocessedOrderItemsCollectionItems = $unprocessedOrderItemsCollection->getItems();

            foreach ($unprocessedOrderItemsCollectionItems as $unprocessedOrderItem) {
                $itemId = $unprocessedOrderItem->getitemId();
                $orderId = $unprocessedOrderItem->getOrderId();
                try {
                    $resultDraftHash = $this->orderHelper->loadPayLoadInformationByOrderId($orderId, $itemId);
                    if ($resultDraftHash) {
                        array_push($uploadedItemsDrafts, $resultDraftHash);
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Loading payload-information failed for item with item-id: '.$itemId.' and order-id'.$orderId);
                }
            }
        }
        return $uploadedItemsDrafts;
    }

    /**
     * @param array $draftIdsToProcess
     */
    private function startProcessing(array $draftIdsToProcess)
    {
        $draftIdsToProcessSuccess = [];
        $draftIdsToProcessFailed = [];
        foreach ($draftIdsToProcess as $draftId) {
            $idToProcess = [];
            array_push($idToProcess, $draftId);
            if (!empty($draftIdsToProcess)){
                try {
                    $this->api->setAsyncOrdered($draftIdsToProcess);
                    array_push($draftIdsToProcessSuccess, $draftId);
                } catch (\Exception $e) {
                    array_push($draftIdsToProcessFailed, $draftId);
                    $this->logger->error('Error on draft processing for draft: '.$draftId.PHP_EOL.'Status-code: '.$e->getCode().PHP_EOL.$e->getMessage().'Line: '.$e->getLine().PHP_EOL.'File: '.$e->getFile());
                }
            }
        }
        $this->logger->notice('Drafts processing failed: '.implode(",", $draftIdsToProcessFailed));
        $this->logger->notice('Drafts processing successfully processed: '.implode(",", $draftIdsToProcessSuccess));
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