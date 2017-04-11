<?php
namespace Rissc\Printformer\Block\Checkout\Cart\Item\Renderer\Actions;

class EditPlugin
{
    /**
     * @var \Rissc\Printformer\Helper\Config
     */
    protected $configHelper;

    /**
     * @param \Rissc\Printformer\Helper\Config $configHelper
     */
    public function __construct(
        \Rissc\Printformer\Helper\Config $configHelper
    ) {
        $this->configHelper = $configHelper;
    }

    /**
     * @param \Magento\Checkout\Block\Cart\Item\Renderer\Actions\Edit $edit
     * @param string $result
     * @return string
     */
    public function afterGetTemplate(\Magento\Checkout\Block\Cart\Item\Renderer\Actions\Edit $edit, $result)
    {
        $edit->setEditItemText($this->configHelper->getEditText());
        return 'Rissc_Printformer::checkout/cart/item/renderer/actions/edit.phtml';
    }
}
