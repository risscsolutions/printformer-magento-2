<?php
namespace Rissc\Printformer\Plugin\Sales\Order\Reorder;

use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart;
use Magento\Framework\DataObject;
use Rissc\Printformer\Helper\Api;
use Rissc\Printformer\Model\DraftFactory;
use Rissc\Printformer\Model\Draft;
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

    /** @var DraftFactory */
    protected $_draftFactory;

    /** @var DraftResource */
    protected $_draftResource;

    /** @var array  */
    private $allRlations = [];

    public function __construct(
        Api $apiHelper,
        Registry $registry,
        Session $customerSession,
        DraftFactory $draftFactory,
        DraftResource $draftResource
    ) {
        $this->_apiHelper = $apiHelper;
        $this->_registry = $registry;
        $this->_customerSession = $customerSession;
        $this->_draftFactory = $draftFactory;
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

            /** @var Draft $draftProcess */
            $draftProcess = $this->_apiHelper->draftProcess($oldDraftId);
            if ($draftProcess->getId()) {
                /** @var Draft $newDraftProcess */
                $newDraftProcess = $this->_draftFactory->create();
                $draftData = $draftProcess->getData();
                unset($draftData['id']);
                unset($draftData['created_at']);
                unset($draftData['order_item_id']);
                unset($draftData['processing_id']);
                $draftData['processing_status'] = 0;
                $newDraftProcess->addData($draftData);

                $newDraftProcess->setDraftId($newDraftId);
                $newDraftProcess->setCopyFrom($oldDraftId);

                $this->_draftResource->save($newDraftProcess);

                $buyRequest->setData('printformer_draftid', $newDraftId);
                $relations = $buyRequest->getData('draft_hash_relations');
                if (!empty($relations[$draftProcess->getPrintformerProductId()])) {
                    $relations[$draftProcess->getPrintformerProductId()] = $newDraftId;
                    $buyRequest->setData('draft_hash_relations', $relations);
                }
            }
        }

        return $originalAddProduct($product, $buyRequest);
    }
}