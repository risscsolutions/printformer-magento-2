<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Rissc\Printformer\Model\Config\Source;

/**
 * @api
 * @since 100.0.2
 */
class DisplayMode implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('Lightbox')],
            ['value' => 1, 'label' => __('Fullscreen')],
            ['value' => 2, 'label' => __('Shop Frame')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            0 => __('Lightbox'),
            1 => __('Fullscreen'),
            2 => __('Shop Frame')
        ];
    }
}
