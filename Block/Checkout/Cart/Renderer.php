<?php
namespace Rissc\Printformer\Block\Checkout\Cart;

/**
 * Class Renderer
 * @package Rissc\Printformer\Block\Checkout\Cart
 */
class Renderer
    extends \Magento\Checkout\Block\Cart\Item\Renderer
{
    /**
     * @return string
     */
    public function getConfigureUrl()
    {
        return $this->getUrl(
            'checkout/cart/configure',
            [
                'id' => $this->getItem()->getId(),
                'product_id' => $this->getItem()->getProduct()->getId()
            ]
        );
    }
}