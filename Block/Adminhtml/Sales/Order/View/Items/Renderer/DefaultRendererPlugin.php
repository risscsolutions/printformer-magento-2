<?php
namespace Rissc\Printformer\Block\Adminhtml\Sales\Order\View\Items\Renderer;

class DefaultRendererPlugin
    extends \Magento\Sales\Block\Adminhtml\Order\View\Items\Renderer\DefaultRenderer
{
    /**
     * @var \Rissc\Printformer\Helper\Url
     */
    protected $urlHelper;

    /**
     * @param \Rissc\Printformer\Helper\Url $urlHelper
     */
    public function __construct(
        \Rissc\Printformer\Helper\Url $urlHelper
    ) {
        $this->urlHelper = $urlHelper;
    }

    /**
     * @param \Magento\Sales\Block\Adminhtml\Order\View\Items\Renderer\DefaultRenderer $renderer
     * @param callable $proceed
     * @param \Magento\Framework\DataObject $item
     * @param $column
     * @param null $field
     * @return string
     */
    public function aroundGetColumnHtml(
        \Magento\Sales\Block\Adminhtml\Order\View\Items\Renderer\DefaultRenderer $renderer,
        \Closure $proceed,
        \Magento\Framework\DataObject $item,
        $column,
        $field = null
    ) {
        $html = $proceed($item, $column, $field);
        if ($column == 'product' && $item->getPrintformerDraftid()) {
            if ($renderer->canDisplayContainer()) {
                $html .= '<div id="printformer-draftid">';
            }

            $html .= '<div><br /><span>' . __('Draft ID') . ':&nbsp;</span>';
            $html .= '<a href="' . $this->getEditorUrl($item) . '" target="_blank">';
            $html .= $renderer->escapeHtml($item->getPrintformerDraftid());
            $html .= '</a></div>';

            if ($item->getPrintformerOrdered()) {
                $html .= '<div><a href="' . $this->getPdfUrl($item) . '" target="_blank">';
                $html .= __('Get PDF');
                $html .= '</a></div>';
            }

            if ($renderer->canDisplayContainer()) {
                $html .= '</div>';
            }
        }
        return $html;
    }

    /**
     * @param \Magento\Framework\DataObject $item
     * @return string
     */
    public function getPdfUrl(\Magento\Framework\DataObject $item)
    {
        return $this->urlHelper->setStoreId($item->getPrintformerStoreid())
            ->getAdminPdfUrl($item->getPrintformerDraftid(), $item->getOrder()->getQuoteId());
    }

    /**
     * @param \Magento\Framework\DataObject $item
     * @return string
     */
    public function getThumbImgUrl(\Magento\Framework\DataObject $item)
    {
        return $this->urlHelper->setStoreId($item->getPrintformerStoreid())
            ->getThumbImgUrl($item->getPrintformerDraftid());
    }

    /**
     * @param \Magento\Framework\DataObject $item
     * @return string
     */
    public function getEditorUrl(\Magento\Framework\DataObject $item)
    {
        return $this->urlHelper->setStoreId($item->getPrintformerStoreid())
            ->getAdminEditorUrl($item->getPrintformerDraftid());
    }
}
