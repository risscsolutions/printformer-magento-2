<?php

namespace Rissc\Printformer\Plugin\Sales\Block\Adminhtml\Order\View\Items\Renderer;

use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Block\Adminhtml\Order\View\Items\Renderer\DefaultRenderer as SubjectDefaultRenderer;
use Rissc\Printformer\Helper\Quote\View as ViewHelper;

class DefaultRenderer
{
    /**
     * @var ViewHelper
     */
    protected $viewHelper;

    /**
     * DefaultRenderer constructor.
     * @param ViewHelper $viewHelper
     */
    public function __construct(
        ViewHelper $viewHelper
    ) {
        $this->viewHelper = $viewHelper;
    }

    /**
     * @param SubjectDefaultRenderer $renderer
     * @param \Closure $proceed
     * @param DataObject $item
     * @param $column
     * @param null $field
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundGetColumnHtml(
        SubjectDefaultRenderer $renderer,
        \Closure $proceed,
        DataObject $item,
        $column,
        $field = null
    ) {
        /** @var Item $item */
        $html = $proceed($item, $column, $field);
        if ($column == 'product' && $item->getPrintformerDraftid()) {
            $product = $item->getProduct();
            $product->getResource()->load($product, $product->getId());

            $html .= $this->viewHelper->getEditorView($item, $product, $renderer);
        }

        return $html;
    }
}