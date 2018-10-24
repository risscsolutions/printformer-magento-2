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

            //olddraftid
            $oldDraftId = $buyRequest['printformer_draftid'];

            //replicate get
            $newDraftId = $this->_apiHelper->getReplicateDraft($oldDraftId);

            //draft_has_relations holen
            //select from catalog_product_printformer_product with printformer_process_id
            $allRelations = [];
            $draftProcess = null;
            foreach ($buyRequest['draft_hash_relations'] as $printformer_product_id => $draft_hash_relation) {
                $sql = "SELECT * FROM {$tableName} WHERE printformer_product_id = {$printformer_product_id} AND product_id = {$product->getId()}";
                $dbRowResults = $connection->fetchAll($sql);

                $allRelations[$printformer_product_id] = $newDraftId;

                foreach ($dbRowResults as $dbRowResult) {
                    $masterId = $dbRowResult['master_id'];
                    $productId = $dbRowResult['product_id'];
                    $intent = $dbRowResult['intent'];
                    $sessionUniqueId = $buyRequest['printformer_unique_session_id'];
                    $customerId = $this->_customerSession->getCustomerId();
                    $printformerProductId = $dbRowResult['printformer_product_id'];

                    //draftprocess() -> alle daten notwendig
                    $draftProcess = $this->_apiHelper->draftProcess($newDraftId, $masterId, $productId, $intent, $sessionUniqueId, $customerId, $printformerProductId);

                    $draftProcess->setData('copy_from', $draft_hash_relation);
                    $this->_draftResource->save($draftProcess);
                }
            }

            //draftid durch replicate ersetzen => buyrequest abändern ['draft_hash_relations', 'draft_id']
            $buyRequest->setData('printformer_draftid', $newDraftId);
            $buyRequest->setData('draft_hash_relations', $allRelations);
        }

        return $originalAddProduct($product, $buyRequest);
    }
}