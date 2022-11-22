<?php
namespace Rissc\Printformer\Plugin\Sales\Order\Reorder;

use Closure;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Registry;
use Rissc\Printformer\Helper\Api;
use Rissc\Printformer\Setup\InstallSchema;

class Plugin
{
    /** @var Api */
    protected $_apiHelper;

    /** @var Registry */
    protected $_registry;

    public function __construct(
        Api $apiHelper,
        Registry $registry
    ) {
        $this->_apiHelper = $apiHelper;
        $this->_registry = $registry;
    }

    /**
     * @param Cart $subject
     * @param Closure $originalAddOrderItem
     * @param $item
     * @return mixed
     */
    public function aroundAddOrderItem(Cart $subject, Closure $originalAddOrderItem, $item)
    {
        if ($this->_registry->registry('printformer_is_reorder')) {
            $this->_registry->unregister('printformer_is_reorder');
        }

        $this->_registry->register('printformer_is_reorder', true);

        return $originalAddOrderItem($item);
    }

    /**
     * @param Cart $subject
     * @param Closure $originalAddProduct
     * @param $product
     * @param $buyRequest
     * @return mixed
     * @throws AlreadyExistsException
     */
    public function aroundAddProduct(Cart $subject, Closure $originalAddProduct, $product, $buyRequest)
    {
        if ($this->_registry->registry('printformer_is_reorder')) {
            $draftIds = $buyRequest->getData(InstallSchema::COLUMN_NAME_DRAFTID);
            if (!empty($draftIds)) {
                $draftHashArray = explode(',', $draftIds);
                foreach ($draftHashArray as $draftId) {
                    $oldDraftId = $draftId;
                    $newDraft = $this->_apiHelper->generateNewReplicateDraft($oldDraftId);
                    if (!empty($newDraft)) {
                        $newDraftId = $newDraft->getDraftId();
                        $relations = $buyRequest->getData('draft_hash_relations');
                        if (!empty($relations[$newDraft->getPrintformerProductId()])) {
                            $relations[$newDraft->getPrintformerProductId()] = $newDraftId;
                            $buyRequest->setData('draft_hash_relations', $relations);
                        }
                    }
                }
            }

            $this->_registry->unregister('printformer_is_reorder');
        }

        return $originalAddProduct($product, $buyRequest);
    }
}
