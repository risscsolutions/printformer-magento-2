<?php
namespace Rissc\Printformer\Plugin\Sales\Order\Reorder;

use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart;
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
        $this->_registry->register('printformer_is_reorder', true);

        return $originalAddOrderItem($item);
    }

    public function aroundAddProduct(Cart $subject, \Closure $originalAddProduct, Product $product, $buyRequest)
    {
        $connection = $this->_draftResource->getConnection();
        $tableName = $connection->getTableName('catalog_product_printformer_product');

        if ($this->_registry->registry('printformer_is_reorder')) {
            $this->_registry->unregister('printformer_is_reorder');
            $oldDraftId = $buyRequest['printformer_draftid'];
            $newDraftId = $this->_apiHelper->getReplicateDraftId($oldDraftId);
            $allRelations = [];
            $draftProcess = null;

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

            $buyRequest->setData('printformer_draftid', $newDraftId);
            $buyRequest->setData('draft_hash_relations', $allRelations);
        }

        return $originalAddProduct($product, $buyRequest);
    }
}