<?php
namespace Rissc\Printformer\Helper;

use Magento\Downloadable\Model\Link;
use Magento\Framework\App\State;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Customer\Model\Session as CustomerSession;
use Rissc\Printformer\Helper\Api\Url as UrlHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Backend\Model\Session as AdminSession;
use Rissc\Printformer\Model\DraftFactory;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Order\ItemFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as ItemCollection;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as ItemCollectionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Downloadable\Model\Product\Type;
use Rissc\Printformer\Helper\Config as PrintformerConfig;

/**
 * Class Order
 * @package Rissc\Printformer\Helper
 */
class Order extends Api
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
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

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
     * Order constructor.
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param UrlHelper $urlHelper
     * @param StoreManagerInterface $storeManager
     * @param DraftFactory $draftFactory
     * @param Session $sessionHelper
     * @param Config $config
     * @param CustomerFactory $customerFactory
     * @param CustomerResource $customerResource
     * @param AdminSession $adminSession
     * @param PrintformerProductAttributes $printformerProductAttributes
     * @param Filesystem $filesystem
     * @param ItemCollectionFactory $itemCollectionFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param ProductRepositoryInterface $productRepository
     * @param Product $product
     * @param Config $printformerConfig
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        UrlHelper $urlHelper,
        StoreManagerInterface $storeManager,
        DraftFactory $draftFactory,
        Session $sessionHelper,
        Config $config,
        CustomerFactory $customerFactory,
        CustomerResource $customerResource,
        AdminSession $adminSession,
        PrintformerProductAttributes $printformerProductAttributes,
        Filesystem $filesystem,
        ItemCollectionFactory $itemCollectionFactory,
        OrderRepositoryInterface $orderRepository,
        OrderItemRepositoryInterface $orderItemRepository,
        ProductRepositoryInterface $productRepository,
        Product $product,
        PrintformerConfig $printformerConfig,
        UrlInterface $urlBuilder
    )
    {
        parent::__construct($context, $customerSession, $urlHelper, $storeManager, $draftFactory, $sessionHelper, $config, $customerFactory, $customerResource, $adminSession, $printformerProductAttributes, $filesystem, $urlBuilder);
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->productRepository = $productRepository;
        $this->product = $product;
        $this->printformerConfig = $printformerConfig;
    }

    /**
     * Check with draft id if order-item has required config-status
     *
     * @param $draftHash
     * @return bool
     */
    public function checkItemByDraftHash($draftHash)
    {
        $process = $this->getDraftProcess($draftHash);
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
        $customerId = $unprocessedOrderItem->getCustomerId();
        $storeId = $unprocessedOrderItem->getStoreId();
        $printformerUserIdentifier = $unprocessedOrderItem->getData('printformer_identification');
        $productId = $unprocessedOrderItem->getProductId();
        $product = $this->productRepository->getById($productId);
        $filesTransferToPrintformer = $product->getCustomAttribute('files_transfer_to_printformer')->getValue();

        if ($filesTransferToPrintformer == 1) {
            //check if user has printformer_identifier and create one if not
            if (!isset($printformerUserIdentifier) && !empty($customerId)){
                $customer = $this->getCustomerById($customerId);
                if (!empty($customer)){
                    $printformerUserIdentifier = $this->loadPrintformerIdentifierOnCustomer($customer);
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
                                $draftProcess = $this->uploadDraftProcess(
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
                                    $orderItemId
                                );
                                $draftHash = $draftProcess->getDraftId();

                                if (isset($draftHash)) {
                                    $this->uploadPdf($draftHash, $linkFile);
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
        } else {
            $this->_logger->debug('cannot upload item cause of files_transfer_to_printformer-config-value on item');
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
     * @param $draftHash
     * @return false|OrderItemInterface
     */
    public function getOrderItemByDraftId($draftHash)
    {
        $orderItem = false;
        $process = $this->_draftFactory->create();

        $draftCollection = $process->getCollection();
        if($draftHash !== null) {
            $draftCollection->addFieldToFilter('draft_id', ['eq' => $draftHash]);
            $process = $draftCollection->getFirstItem();

            if ($process->getId()) {
                $process = $draftCollection->getLastItem();
                $orderItemId = $process->getOrderItemId();
                if(!empty($orderItemId)){
                    $orderItem = $this->orderItemRepository->get($orderItemId);
                }
            }
        }

        return $orderItem;
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
        $templateIdentifier = $this->scopeConfig->getValue('printformer/general/printformer_upload_template_id', ScopeInterface::SCOPE_STORES, $storeId);
        $defaultTemplateIdentifier = $this->scopeConfig->getValue('printformer/general/printformer_upload_template_id', ScopeInterface::SCOPE_STORES, 0);
        if (!isset($templateIdentifier) && isset($defaultTemplateIdentifier)){
            $templateIdentifier = $defaultTemplateIdentifier;
        } elseif(!isset($templateIdentifier) && !isset($defaultTemplateIdentifier)) {
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