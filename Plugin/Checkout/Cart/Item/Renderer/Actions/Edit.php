<?php

namespace Rissc\Printformer\Plugin\Checkout\Cart\Item\Renderer\Actions;

use Rissc\Printformer\Helper\Config;
use Magento\Checkout\Block\Cart\Item\Renderer\Actions\Edit as SubjectEdit;

class Edit
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * Edit constructor.
     * @param Config $configHelper
     */
    public function __construct(
        Config $configHelper
    ) {
        $this->configHelper = $configHelper;
    }

    /**
     * @param SubjectEdit $edit
     * @param string $result
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterGetTemplate(SubjectEdit $edit, $result)
    {
        $edit->setEditItemText($this->configHelper->getEditText());
        return 'Rissc_Printformer::checkout/cart/item/renderer/actions/edit.phtml';
    }
}