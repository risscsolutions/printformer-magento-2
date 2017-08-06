<?php

namespace Rissc\Printformer\Block\Checkout\Cart\Item\Renderer\Actions;

use Rissc\Printformer\Helper\Config;
use Magento\Checkout\Block\Cart\Item\Renderer\Actions\Edit;

class EditPlugin
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * EditPlugin constructor.
     * @param Config $configHelper
     */
    public function __construct(
        Config $configHelper
    ) {
        $this->configHelper = $configHelper;
    }

    /**
     * @param Edit $edit
     * @param string $result
     * @return string
     */
    public function afterGetTemplate(Edit $edit, $result)
    {
        $edit->setEditItemText($this->configHelper->getEditText());
        return 'Rissc_Printformer::checkout/cart/item/renderer/actions/edit.phtml';
    }
}
