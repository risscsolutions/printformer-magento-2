<?php
namespace Rissc\Printformer\Plugin\Sales\Order\Reorder;

use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart;
use Magento\Framework\DataObject;
use Rissc\Printformer\Helper\Api;
use Magento\Framework\Registry;
use Magento\Customer\Model\Session;
use Rissc\Printformer\Model\ResourceModel\Draft as DraftResource;

class Plugin
{
    /** @var Api */
    protected $_apiHelper;

    /** @var Registry */
    protected $_registry;

    /** @var Session */
    protected $_customerSession;

    /** @var DraftResource */
    protected $_draftResource;

    /** @var array  */
    private $allRlations = [];

    public function __construct(
        Api $apiHelper,
        Registry $registry,
        Session $customerSession,
        DraftResource $draftResource
    ) {
        $this->_apiHelper = $apiHelper;
        $this->_registry = $registry;
        $this->_customerSession = $customerSession;
        $this->_draftResource = $draftResource;
    }

    /**
     * @param Cart $subject
     * @param \Closure $originalAddOrderItem
     * @param $item
     * @return mixed
     */
    public function aroundAddOrderItem(Cart $subject, \Closure $originalAddOrderItem, $item)
    {
        if ($this->_registry->registry('printformer_is_reorder')) {
            $this->_registry->unregister('printformer_is_reorder');
        }

        $this->_registry->register('printformer_is_reorder', true);

        return $originalAddOrderItem($item);
    }

    /**
     * @param Cart $subject
     * @param \Closure $originalAddProduct
     * @param Product $product
     * @param $buyRequest
     * @return mixed
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function aroundAddProduct(Cart $subject, \Closure $originalAddProduct, Product $product, $buyRequest)
    {
        if ($this->_registry->registry('printformer_is_reorder')) {
            $this->_registry->unregister('printformer_is_reorder');
            $oldDraftId = $buyRequest['printformer_draftid'];
            $newDraftId = $this->_apiHelper->getReplicateDraftId($oldDraftId);
            $draftProcess = null;

            $this->createDraftProcess($buyRequest, $product, $oldDraftId, $newDraftId);

            $buyRequest->setData('printformer_draftid', $newDraftId);
            $buyRequest->setData('draft_hash_relations', $this->getAllRealtions());
        }

        return $originalAddProduct($product, $buyRequest);
    }

    /**
     * @param DataObject $buyRequest
     * @param Product $product
     * @param string $oldDraftId
     * @param string $newDraftId
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function createDraftProcess(DataObject $buyRequest, Product $product, string $oldDraftId, string $newDraftId)
    {
        $connection = $this->_draftResource->getConnection();
        $tableName = $connection->getTableName('catalog_product_printformer_product');

        foreach ($buyRequest['draft_hash_relations'] as $printformerProductId => $draftHashRelation) {
            $sql = "SELECT * FROM {$tableName} WHERE printformer_product_id = {$printformerProductId} AND product_id = {$product->getId()}";
            $dbRowResults = $connection->fetchAll($sql);
            $allRelations[$printformerProductId] = $newDraftId;

            foreach ($dbRowResults as $dbRowResult) {
                $masterId = $dbRowResult['master_id'];
                $productId = $dbRowResult['product_id'];
                $intent = $dbRowResult['intent'];
                $sessionUniqueId = $buyRequest['printformer_unique_session_id'];
                $customerId = $this->_customerSession->getCustomerId();
                $printformerProductIdDb = $dbRowResult['printformer_product_id'];
                $draftProcess = $this->_apiHelper->draftProcess($newDraftId, $masterId, $productId, $intent, $sessionUniqueId, $customerId, $printformerProductIdDb);

                $draftProcess->setData('copy_from', $oldDraftId);
                $this->_draftResource->save($draftProcess);
            }
        }

        $this->setAllRealtions($allRelations);
    }

    /**
     * @param $allRealtions
     */
    public function setAllRealtions($allRealtions)
    {
        $this->allRlations = $allRealtions;
    }

    /**
     * @return array
     */
    public function getAllRealtions()
    {
        return $this->allRlations;
    }
}