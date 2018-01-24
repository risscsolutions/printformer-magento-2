<?php

namespace Rissc\Printformer\Block\Adminhtml\Sales\Order\View\Items\Renderer;

use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Block\Adminhtml\Order\View\Items\Renderer\DefaultRenderer;
use Rissc\Printformer\Helper\Api\Url;
use Rissc\Printformer\Helper\Quote\View as ViewHelper;

class DefaultRendererPlugin
{
    /** @var Url */
    protected $_urlHelper;

    /** @var ViewHelper */
    protected $_viewHelper;

    /**
     * DefaultRendererPlugin constructor.
     *
     * @param Url        $urlHelper
     * @param ViewHelper $viewHelper
     */
    public function __construct(
        Url $urlHelper,
        ViewHelper $viewHelper
    ) {
        $this->_urlHelper = $urlHelper;
        $this->_viewHelper = $viewHelper;
    }

    /**
     * @param DefaultRenderer $renderer
     * @param \Closure        $proceed
     * @param DataObject      $item
     * @param                 $column
     * @param null            $field
     *
     * @return mixed|string
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundGetColumnHtml(
        DefaultRenderer $renderer,
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

            $html .= $this->_viewHelper->getEditorView($item, $product, $renderer);
        }

        return $html;
    }

    /**
     * @param DataObject $item
     *
     * @return string
     */
    public function getPdfUrl(\Magento\Framework\DataObject $item)
    {
        return $this->_urlHelper->setStoreId($item->getPrintformerStoreid())
            ->getAdminPdf($item->getPrintformerDraftid(), $item->getOrder()->getQuoteId());
    }

    /**
     * @param DataObject $item
     *
     * @return string
     */
    public function getThumbImgUrl(\Magento\Framework\DataObject $item)
    {
        return $this->_urlHelper->setStoreId($item->getPrintformerStoreid())
            ->getThumbnail($item->getPrintformerDraftid());
    }
}
