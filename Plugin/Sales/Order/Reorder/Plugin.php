<?php
namespace Rissc\Printformer\Plugin\Sales\Order\Reorder;

use Closure;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Registry;
use Rissc\Printformer\Helper\Api;
use Rissc\Printformer\Helper\Cart as CartHelper;

class Plugin
{
    /**
     * @var Api
     */
    protected $apiHelper;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var CartHelper
     */
    private CartHelper $cartHelper;

    /**
     * @param Registry $registry
     * @param CartHelper $cartHelper
     */
    public function __construct(
        Registry $registry,
        CartHelper $cartHelper
    ) {
        $this->registry = $registry;
        $this->cartHelper = $cartHelper;
    }

    /**
     * @param Cart $subject
     * @param Closure $originalAddOrderItem
     * @param $item
     * @return mixed|null
     */
    public function aroundAddOrderItem(Cart $subject, Closure $originalAddOrderItem, $item)
    {
        if ($this->registry->registry('printformer_is_reorder')) {
            $this->registry->unregister('printformer_is_reorder');
        }

        $this->registry->register('printformer_is_reorder', true);

        return $originalAddOrderItem($item);
    }

    /**
     * @param Cart $subject
     * @param Closure $originalAddProduct
     * @param $product
     * @param $buyRequest
     * @return mixed|null
     */
    public function aroundAddProduct(Cart $subject, Closure $originalAddProduct, $product, $buyRequest)
    {
        $isReordered = $this->registry->registry('printformer_is_reorder');

        $this->cartHelper->prepareDraft($buyRequest, $isReordered);

        return $originalAddProduct($product, $buyRequest);
    }
}
