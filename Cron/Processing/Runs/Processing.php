<?php
namespace Rissc\Printformer\Cron\Processing\Runs;

use Psr\Log\LoggerInterface;
use Rissc\Printformer\Helper\Order;
use Rissc\Printformer\Helper\Api;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as ItemCollection;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as ItemCollectionFactory;
use Rissc\Printformer\Helper\Config as PrintformerConfig;


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
     * @var null|string
     */
    protected $toDateTime = null;

    /**
     * @var null|int
     */
    protected $fromDateTime = null;

    /**
     * @var null|int
     */
    protected $orderItemIdsToFilter = null;

    /**
     * @var int
     */
    protected $validUploadProcessingCountSmallerThen = 0;

    /**
     * @var ItemCollection
     */
    private $itemCollectionFactory;

    /**
     * @var PrintformerConfig
     */
    private $printformerConfig;

    /**
     * Processing constructor.
     * @param LoggerInterface $logger
     * @param Order $orderHelper
     * @param Api $api
     * @param ItemCollectionFactory $itemCollectionFactory
     * @param PrintformerConfig $printformerConfig
     */
    public function __construct(
        LoggerInterface $logger,
        Order $orderHelper,
        Api $api,
        ItemCollectionFactory $itemCollectionFactory,
        PrintformerConfig $printformerConfig
    )
    {
        $this->logger = $logger;
        $this->orderHelper = $orderHelper;
        $this->api = $api;
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->printformerConfig = $printformerConfig;
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

        $this->uploadPrintformerOrderUploadItems();

        $unprocessedPrintformerOrderItemDraftsCollection = $this->getUnprocessedPrintformerOrderItemsDrafts();
        $unprocessedPrintformerOrderItems = $unprocessedPrintformerOrderItemDraftsCollection->getItems();
        if (!empty($unprocessedPrintformerOrderItems)){
            foreach ($unprocessedPrintformerOrderItems as $orderItem) {
                if(!empty($draftId = $orderItem['printformer_draftid'])){
                    try {
                        $storeId = $orderItem['store_id'];

                        $draftToSync = [];
                        array_push($draftToSync, $draftId);
                        if (empty($this->orderItemIdsToFilter)){
                            $this->orderHelper->updateProcessingCountByOrderItem($orderItem);
                        }
                        $incrementOrderId = $orderItem->getIncrementId();
                        $this->logger->debug('normal drafts to process found:'.implode(",", $draftToSync));
                        $this->api->setAsyncOrdered($draftToSync, $storeId);
                    } catch (\Exception $e) {
                        $this->logger->critical($e);
                    } finally {
                        $this->api->setProcessingStateOnOrderItemByDraftId($draftId, $this->api::ProcessingStateAfterCron);
                    }
                }
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
        /**
         * @var $collection ItemCollection
         */
        $collection = $this->itemCollectionFactory->create();

        $collection
            ->addAttributeToSelect('*')
            ->addFieldToFilter('main_table.printformer_ordered', 'eq' == '0')
            ->addFieldToFilter('main_table.product_type', ['eq' => 'downloadable'])
            ->setOrder(
                'main_table.Updated_at',
                'DESC'
            );

        if (isset($this->toDateTime, $this->fromDateTime)){
            $collection->addFieldToFilter('main_table.created_at', array('from'=>$this->fromDateTime, 'to'=>$this->toDateTime));
            $collection->addFieldToFilter('main_table.printformer_upload_processing_count', ['lt' => $this->validUploadProcessingCountSmallerThen]);
        }

        if (isset($this->orderItemIdsToFilter)){
            $collection->addFieldToFilter('main_table.item_id', ['in' => $this->orderItemIdsToFilter]);
        }

        $collection->join(
            ['order' => $collection->getTable('sales_order')],
            'order.entity_id = main_table.order_id',
            ['*']
        );

        $collection->getSelect()
            ->joinLeft(
                ['customer' => $collection->getTable('customer_entity')],
                'customer.entity_id = order.customer_id',
                ['customer.entity_id', 'customer.printformer_identification']
            );

        return $collection;
    }

    /**
     * Get unprocessed Printformer-Upload-items with cron specific filter
     * @return ItemCollection
     */
    private function getUnprocessedPrintformerOrderItemsDrafts(){
        /**
         * @var $collection ItemCollection
         */
        $collection = $this->itemCollectionFactory->create();

        $validOrderStatus = $this->printformerConfig->getOrderStatus();

        $collection
            ->addFieldToSelect('*')
            ->addFieldToFilter('pfdrafts.intent', ['neq' => 'upload'])
            ->addFieldToFilter('pfdrafts.processing_id', 'eq' == '')
            ->addFieldToFilter('order.status', ['in' => $validOrderStatus])
            ->setOrder(
                'main_table.updated_at',
                'desc'
            );

        if (isset($this->toDateTime, $this->fromDateTime)) {
            $collection->addFieldToFilter('main_table.created_at', array('from' => $this->fromDateTime, 'to' => $this->toDateTime));
            $collection->addFieldToFilter('main_table.printformer_upload_processing_count', ['lt' => $this->validUploadProcessingCountSmallerThen]);
        }

        $collection->getSelect()
            ->join(
            ['order' => $collection->getTable('sales_order')],
            'order.entity_id = main_table.order_id',
            ['*']
        );

        $collection->getSelect()
            ->joinLeft(
                ['pfdrafts' => $collection->getTable('printformer_draft')],
                'pfdrafts.draft_id = main_table.printformer_draftid',
                []
            );

        return $collection;
    }

    /**
     * Init upload for upload-order-items
     */
    protected function uploadPrintformerOrderUploadItems(): void
    {
        $unprocessedOrderItemsCollection = $this->getUnprocessedPrintformerOrderUploadItemsCollection();

        $uploadedItemsDrafts = [];
        if (isset($unprocessedOrderItemsCollection)){
            foreach ($unprocessedOrderItemsCollection->getItems() as $unprocessedOrderItem) {
                try {
                    $resultDraftHash = $this->orderHelper->loadPayLoadInformationByOrderIdAndUploadFile($unprocessedOrderItem);
                    if ($resultDraftHash) {
                        array_push($uploadedItemsDrafts, $resultDraftHash);
                    }
                } catch (\Exception $e) {
                    $this->logger->debug('Loading payload-information failed for item with item-id: '.$unprocessedOrderItem->getItemId().' and order-id'.$unprocessedOrderItem->getOrderId());
                }
            }
        }
    }

    /**
     * Reset all previous used filters
     */
    protected function resetProcessingFilters()
    {
        $this->toDateTime = null;
        $this->fromDateTime = null;
    }
}