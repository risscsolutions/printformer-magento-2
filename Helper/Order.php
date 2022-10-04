<?php
namespace Rissc\Printformer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Downloadable\Model\Link;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Customer;
use Magento\Sales\Model\Order\ItemFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as ItemCollection;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as ItemCollectionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Downloadable\Model\Product\Type;
use Rissc\Printformer\Model\DraftFactory;
use Rissc\Printformer\Helper\Config as PrintformerConfig;
use Rissc\Printformer\Helper\Api as ApiHelper;
use Rissc\Printformer\Helper\Config as ConfigHelper;

/**
 * Class Order
 * @package Rissc\Printformer\Helper
 */
class Order extends AbstractHelper
{
    /**
     * @var ItemCollection
     */
    private $itemCollectionFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Product
     */
    private $product;

    /**
     * @var Config
     */
    private $printformerConfig;

    /**
     * @var ItemFactory
     */
    private $itemFactory;

    /**
     * @var Api
     */
    private Api $apiHelper;

    /**
     * @var OrderItemRepositoryInterface
     */
    private OrderItemRepositoryInterface $orderItemRepository;

    /**
     * @var CustomerFactory
     */
    private CustomerFactory $_customerFactory;

    /**
     * @var Config
     */
    private Config $configHelper;

    /**
     * @param ItemCollectionFactory $itemCollectionFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param ProductRepositoryInterface $productRepository
     * @param Product $product
     * @param Config $printformerConfig
     * @param ItemFactory $itemFactory
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param Api $apiHelper
     * @param CustomerFactory $_customerFactory
     * @param Config $configHelper
     */
    public function __construct(
        ItemCollectionFactory $itemCollectionFactory,
        OrderRepositoryInterface $orderRepository,
        ProductRepositoryInterface $productRepository,
        Product $product,
        PrintformerConfig $printformerConfig,
        ItemFactory $itemFactory,
        OrderItemRepositoryInterface $orderItemRepository,
        ApiHelper $apiHelper,
        CustomerFactory $_customerFactory,
        ConfigHelper $configHelper
    )
    {
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->product = $product;
        $this->printformerConfig = $printformerConfig;
        $this->itemFactory = $itemFactory;
        $this->apiHelper = $apiHelper;
        $this->orderItemRepository = $orderItemRepository;
        $this->_customerFactory = $_customerFactory;
        $this->configHelper = $configHelper;
    }

    /**
     * Check with draft id if order-item has required config-status
     *
     * @param $draftHash
     * @return bool
     */
    public function checkItemByDraftHash($draftHash)
    {
        $process = $this->apiHelper->getDraftProcess($draftHash);
        $orderItemId = $process->getOrderItemId();
        if (!empty($orderItemId)) {
            $collection = $this->itemCollectionFactory->create();
            $collection
                ->addFieldToFilter('main_table.item_id', ['eq' => $orderItemId]);

            $orderItem = $collection->getFirstItem();
            if (isset($orderItem['order_id'])){
                $order = $this->getOrderById($orderItem['order_id']);
                $orderStatus = $order->getStatus();
                $validOrderStatus = $this->printformerConfig->getOrderStatus();
                if (!empty($orderStatus) && !empty($validOrderStatus)){
                    if (in_array($orderStatus, $validOrderStatus)){
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param $unprocessedOrderItem
     * @return string|null
     */
    public function loadPayLoadInformationByOrderIdAndUploadFile($unprocessedOrderItem)
    {
        $orderItemId = $unprocessedOrderItem->getItemId();
        $orderItem = $this->getOrderItemById($orderItemId);
        $this->updateProcessingCountByOrderItem($orderItem);

        $resultDraftHash = null;
        $currentDraftHash = null;
        $orderId = $unprocessedOrderItem->getOrderId();
        $orderIncrementId = $unprocessedOrderItem->getIncrementId();
        $customerId = $unprocessedOrderItem->getCustomerId();
        $storeId = $unprocessedOrderItem->getStoreId();
        $printformerUserIdentifier = $unprocessedOrderItem->getData('printformer_identification');
        $productId = $unprocessedOrderItem->getProductId();
        $product = $this->productRepository->getById($productId);
        $filesTransferToPrintformer = 0;

        if (!empty($product)){
            $filesTransferToPrintformerAttribute = $product->getCustomAttribute('files_transfer_to_printformer');
            if (!empty($filesTransferToPrintformerAttribute)) {
                $filesTransferToPrintformer = $filesTransferToPrintformerAttribute->getValue();
            }

            if ($filesTransferToPrintformer == 1) {
                //check if user has printformer_identifier and create one if not
                if (!isset($printformerUserIdentifier) && !empty($customerId)){
                    $customer = $this->getCustomerById($customerId);
                    if (!empty($customer)){
                        $printformerUserIdentifier = $this->apiHelper->loadPrintformerIdentifierOnCustomer($customer);
                    }
                }

                if (isset($printformerUserIdentifier)) {
                    $templateIdentifier = $this->getTemplateIdentifier($storeId); //$order->getStoreId()

                    //start upload process and get draft from process
                    try {
                        if ($product->getTypeId() === Type::TYPE_DOWNLOADABLE) {
                            $links = $product->getTypeInstance()->getLinks($product);

                            /**
                             * Upload all link-files of product, if some product upload failes, clear result to not process the
                             * corresponding draft
                             *
                             * @var Link $link
                             */
                            foreach ($links as $link) {
                                $linkFile = $link->getLinkFile();
                                if ($link->getId() && $linkFile) {
                                    $draftProcess = $this->apiHelper->uploadDraftProcess(
                                        null,
                                        0,
                                        $productId,
                                        null,
                                        $customerId,
                                        null,
                                        false,
                                        $printformerUserIdentifier,
                                        $templateIdentifier,
                                        $orderId,
                                        $storeId,
                                        $orderItemId,
                                        $orderIncrementId
                                    );

                                    $newDraftIds = $draftProcess->getDraftId();
                                    $item = $this->itemFactory->create();
                                    $item->getResource()->load($item, $orderItemId);
                                    $currentDraftIds = $item->getPrintformerDraftid();
                                    if (isset($currentDraftIds)){
                                        $allDrafts = $currentDraftIds . ',' . $newDraftIds;
                                    } else {
                                        $allDrafts = $newDraftIds;
                                    }
                                    $item->setPrintformerDraftid($allDrafts);
                                    $item->getResource()->save($item);

                                    if (isset($newDraftIds)) {
                                        $this->uploadPdf($newDraftIds, $linkFile);
                                    }
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        $this->_logger->debug('Upload failed for item with item-id: ' . $orderItemId . ' and order-id' . $orderId . ' with template identifier: ' . $templateIdentifier);
                        $this->_logger->debug($e->getMessage());
                    }
                } else {
                    $this->_logger->debug('user identifier not found');
                }
            }
        }

        return $resultDraftHash;
    }

    /**
     * @param $orderId
     * @return OrderInterface
     */
    public function getOrderById($orderId)
    {
        return $this->orderRepository->get($orderId);
    }

    /**
     * @param $orderItemId
     * @return OrderItemInterface
     */
    public function getOrderItemById($orderItemId)
    {
        return $this->orderItemRepository->get($orderItemId);
    }

    /**
     * @param $customerId
     * @return Customer
     */
    public function getCustomerById($customerId)
    {
        return $this->_customerFactory->create()->load($customerId);
    }

    /**
     * @param OrderInterface $order
     * @return int|mixed
     */
    public function getTemplateIdentifier($storeId)
    {
        $templateIdentifier = $this->configHelper->getUploadTemplateId();
        if (empty($templateIdentifier)) {
            $templateIdentifier = 0;
        }

        return $templateIdentifier;
    }

    /**
     * Save processed order by order-item
     *
     * @param OrderItemInterface $orderItem
     */
    public function updateProcessingCountByOrderItem(OrderItemInterface $orderItem)
    {
        $printformerUploadProcessingCount = $orderItem->getPrintformerUploadProcessingCount();
        $orderItem->setPrintformerUploadProcessingCount($printformerUploadProcessingCount+1);
        $orderItem->getResource()->save($orderItem);
    }
}