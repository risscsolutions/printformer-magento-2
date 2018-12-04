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

            $oldDraftId = $buyRequest->getPrintformerDraftid();
            $draftProcessOld = $this->_apiHelper->draftProcess($oldDraftId);

            if ($draftProcessOld->getId()) {
                $newDraftId = $this->_apiHelper->getReplicateDraft($oldDraftId);

                $draftProccess = $this->_apiHelper->draftProcess(
                    $newDraftId,
                    null,
                    $draftProcessOld->getProductId(),
                    $draftProcessOld->getIntent(),
                    $draftProcessOld->getSessionUniqueId(),
                    $draftProcessOld->getCustomerId()
                );

                $draftProccess->setData('copy_from', $oldDraftId);
                $buyRequest->setData('printformer_draftid', $newDraftId);
                $this->_draftResource->save($draftProccess);
            }
        }

        return $originalAddProduct($product, $buyRequest);
    }
}