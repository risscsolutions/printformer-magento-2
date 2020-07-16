<?php
namespace Rissc\Printformer\Cron;

use Psr\Log\LoggerInterface;
use Rissc\Printformer\Helper\Order;
use Rissc\Printformer\Helper\Config;
use Rissc\Printformer\Helper\Api;

/**
 * Class of cron processing
 *
 * Class Process
 * @package Rissc\Printformer\Cron
 */
class Process
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Order
     */
    private $orderHelper;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Api
     */
    private $api;

    /**
     * Process constructor.
     * @param LoggerInterface $logger
     * @param Order $orderHelper
     * @param Config $config
     * @param Api $api
     */
    public function __construct(
        LoggerInterface $logger,
        Order $orderHelper,
        Config $config,
        Api $api
    )
    {
        $this->logger = $logger;
        $this->orderHelper = $orderHelper;
        $this->config = $config;
        $this->api = $api;
    }

    public function execute()
    {
        $this->logger->notice('--------------------------------Cron started--------------------------------');
        $uploadedItemsDrafts = [];
        $unprocessedPrintformerOrderItemDrafts = [];

        $unprocessedOrderItemsCollection = $this->orderHelper->getUnprocessedPrintformerOrderUploadItems();
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

        $unprocessedPrintformerOrderItemDraftsCollection = $this->orderHelper->getUnprocessedPrintformerOrderItemDrafts();
        if (isset($unprocessedPrintformerOrderItemDraftsCollection)){
            $unprocessedPrintformerOrderItemDrafts = $unprocessedPrintformerOrderItemDraftsCollection->getData();
            if (!empty($unprocessedPrintformerOrderItemDrafts)){
                $unprocessedPrintformerOrderItemDrafts = array_map('current', $unprocessedPrintformerOrderItemDrafts);
            }
        }

        $draftIdsToProcess = array_merge($uploadedItemsDrafts, $unprocessedPrintformerOrderItemDrafts);
        $draftIdsToProcess = array_unique($draftIdsToProcess);

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
        $this->logger->notice('--------------------------------Cron finished-------------------------------');
        return $this;
    }
}