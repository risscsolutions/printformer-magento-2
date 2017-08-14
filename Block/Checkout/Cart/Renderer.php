<?php

namespace Rissc\Printformer\Block\Checkout\Cart;

use Magento\Checkout\Block\Cart\Item\Renderer as ItemRenderer;

class Renderer extends ItemRenderer
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