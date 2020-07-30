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
     * @return ItemCollection
     */
    public function getUnprocessedPrintformerOrderUploadItems()
    {
        /**
         * @var $collection ItemCollection
         */
        $collection = $this->itemCollectionFactory->create();

        $requiredOrderStatus = $this->getPfOrderStatus();
        $collection
            ->addAttributeToSelect('*')
            ->addFieldToFilter('main_table.printformer_ordered', 'eq' == '0')
            ->addFieldToFilter('main_table.product_type', ['eq' => 'downloadable'])
            ->addFieldToFilter('order.status', ['eq' => $requiredOrderStatus])
            ->join(
                ['order' => $collection->getTable('sales_order')],
                'order.entity_id = main_table.order_id',
                []
            )
            ->setOrder(
                'main_table.Updated_at',
                'DESC'
            );

        return $collection;
    }

    /**
     * @return ItemCollection
     */
    public function getUnprocessedPrintformerOrderItemDrafts()
    {
        /**
         * @var $collection ItemCollection
         */
        $collection = $this->itemCollectionFactory->create();

        $requiredOrderStatus = $this->getPfOrderStatus();

        $collection
            ->addAttributeToSelect('printformer_draftid')
            ->addFieldToFilter('main_table.printformer_ordered', 'eq' == '0')
            ->addFieldToFilter('main_table.printformer_draftid', ['neq' => 'NULL'])
            ->addFieldToFilter('main_table.product_type', ['neq' => 'downloadable'])
            ->addFieldToFilter('order.status', ['eq' => $requiredOrderStatus])
            ->addFieldToFilter('pfdrafts.intent', ['neq' => 'upload'])
            ->join(
                ['order' => $collection->getTable('sales_order')],
                'order.entity_id = main_table.order_id',
                []
            )
            ->join(
                ['pfdrafts' => $collection->getTable('printformer_draft')],
                'pfdrafts.draft_id = main_table.printformer_draftid',
                []
            )
            ->setOrder(
                'main_table.updated_at',
                'desc'
            );

        return $collection;
    }

    /**
     * @param $orderId
     * @param $orderItemId
     * @return array|mixed|null
     */
    public function loadPayLoadInformationByOrderIdAndUploadFile($orderId, $orderItemId)
    {
        $resultDraftHash = null;
        $orderItem = $this->getOrderItemById($orderItemId);
        $productId = $orderItem->getProductId();
        $product = $this->getProductById($productId);
        $filesTransferToPrintformer = $product->getCustomAttribute('files_transfer_to_printformer');

        if ($filesTransferToPrintformer) {
            //check if user has printformer_identifier and create one if not
            $order = $this->getOrderById($orderId);
            $customerId = $order->getCustomerId();
            $customer = $this->getCustomerById($customerId);
            $printformerUserIdentifier = $customer->getData('printformer_identification');
            if (!isset($printformerUserIdentifier)){
                $this->loadPrintformerIdentifierOnCustomer($customer);
            }

            $printformerProductId = $this->getPrintformerProduct($productId);
            $templateIdentifier = $this->getTemplateIdentifier($order);

            //start upload process and get draft from process
            try {
                $draftProcess = $this->uploadDraftProcess(
                    null,
                    0,
                    $productId,
                    null,
                    $customerId,
                    $printformerProductId,
                    false,
                    $printformerUserIdentifier,
                    $templateIdentifier,
                    $orderId,
                    $order->getStoreId()
                );
                $draftHash = $draftProcess->getDraftId();
            } catch (\Exception $e) {
                $this->_logger->debug('Upload failed for item with item-id: '.$orderItemId.' and order-id'.$orderId.' with template identifier: '.$templateIdentifier);
                $this->_logger->debug($e->getMessage());
            }

            if ($product->getTypeId() === Type::TYPE_DOWNLOADABLE && isset($draftHash)) {
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
                        if ($this->uploadPdf($draftHash, $linkFile)){
                            $resultDraftHash = $draftHash;
                        } else {
                            $resultDraftHash = null;
                            break;
                        }
                    }
                }
            }
        } else {
            $draftHash = $orderItem->getPrintformerDraftid();
            if (isset($draftHash)){
                $resultDraftHash = $draftHash;
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
     * @param $productId
     * @return ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductById($productId)
    {
        return $this->productRepository->getById($productId);
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
     * @param $productId
     * @return int|mixed
     */
    public function getPrintformerProduct($productId)
    {
        $printformerProductId = 0;
        $connection = $this->product->getResource()->getConnection();
        $select = $connection->select()
            ->from('catalog_product_printformer_product')
            ->where('product_id = ?', intval($productId));

        $check = $connection->fetchAll($select);
        if (!empty($check)){
            $printformerProductId = $check[0]['printformer_product_id'];
        }

        return $printformerProductId;
    }

    /**
     * @param OrderInterface $order
     * @return int|mixed
     */
    public function getTemplateIdentifier($order)
    {
        $templateIdentifier = $this->scopeConfig->getValue('printformer/general/printformer_upload_template_id', ScopeInterface::SCOPE_STORES, $order->getStoreId());
        $defaultTemplateIdentifier = $this->scopeConfig->getValue('printformer/general/printformer_upload_template_id', ScopeInterface::SCOPE_STORES, 0);
        if (!isset($templateIdentifier) && isset($defaultTemplateIdentifier)){
            $templateIdentifier = $defaultTemplateIdentifier;
        } elseif(!isset($templateIdentifier) && !isset($defaultTemplateIdentifier)) {
            $templateIdentifier = 0;
        }
        return $templateIdentifier;
    }

    /**
     * @return string
     */
    private function getPfOrderStatus()
    {
        $pfRequiredOrderStatus = $this->printformerConfig->getOrderStatus();
        if(!isset($pfRequiredOrderStatus)){
            $pfRequiredOrderStatus = 'pending';
        } else {
            $pfRequiredOrderStatus = array_values($pfRequiredOrderStatus)[0];;
        }
        return $pfRequiredOrderStatus;
    }
}