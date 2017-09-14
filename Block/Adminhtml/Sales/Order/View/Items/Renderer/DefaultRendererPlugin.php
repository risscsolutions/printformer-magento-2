<?php

namespace Rissc\Printformer\Block\Adminhtml\Sales\Order\View\Items\Renderer;

use Magento\Framework\DataObject;
use Magento\Sales\Block\Adminhtml\Order\View\Items\Renderer\DefaultRenderer;
use Rissc\Printformer\Helper\Url;

class DefaultRendererPlugin extends DefaultRenderer
{
    /**
     * @var Url
     */
    protected $_urlHelper;

    /**
     * @param Url $urlHelper
     */
    public function __construct(
        Url $urlHelper
    ) {
        $this->_urlHelper = $urlHelper;
    }

    /**
     * @param DefaultRenderer $renderer
     * @param \Closure $proceed
     * @param DataObject $item
     * @param $column
     * @param null $field
     * @return string
     */
    public function aroundGetColumnHtml(
        DefaultRenderer $renderer,
        \Closure $proceed,
        DataObject $item,
        $column,
        $field = null
    ) {
        $html = $proceed($item, $column, $field);
        if ($column == 'product' && $item->getPrintformerDraftid()) {
            if ($renderer->canDisplayContainer()) {
                $html .= '<div id="printformer-draftid">';
            }

            $html .= '<div><br /><span>' . __('Draft ID') . ':&nbsp;</span>';
            $html .= '<span>';
            $html .= $renderer->escapeHtml($item->getPrintformerDraftid());
            $html .= '</span></div>';
            $html .= '<!-- Trigger the modal with a button -->
                      <button type="button" class="btn btn-info btn-lg" data-url="'.$this->getEditorUrl($item).'" id="openModal">Open Editor</button>';
            $html .= '<div id="printformer-editor-main"></div>';

            if ($item->getPrintformerOrdered()) {
                $html .= '<div style="margin-top: 5px;"><a class="action-default scalable action-save action-secondary" href="' . $this->getPdfUrl($item) . '" target="_blank">';
                $html .= __('Show print file');
                $html .= '</a></div>';
            }

            if ($renderer->canDisplayContainer()) {
                $html .= '</div>';
            }
        }

        return $html;
    }

    /**
     * @param DataObject $item
     * @return string
     */
    public function getPdfUrl(\Magento\Framework\DataObject $item)
    {
        return $this->_urlHelper->setStoreId($item->getPrintformerStoreid())
            ->getAdminPdfUrl($item->getPrintformerDraftid(), $item->getOrder()->getQuoteId());
    }

    /**
     * @param DataObject $item
     * @return string
     */
    public function getThumbImgUrl(\Magento\Framework\DataObject $item)
    {
        return $this->_urlHelper->setStoreId($item->getPrintformerStoreid())
            ->getThumbImgUrl($item->getPrintformerDraftid());
    }

    /**
     * @param DataObject $item
     * @return string
     */
    public function getEditorUrl(\Magento\Framework\DataObject $item)
    {
        return $this->_urlHelper->getAdminEditorUrl($item->getPrintformerDraftid());
        $editorUrl = $this->_urlHelper->getDraftEditorUrl($item->getPrintformerDraftid());

        $encodedUrl = urlencode(base64_encode("http://magento2.dev/admin"));
        $urlParts = explode('?', $editorUrl);
        $parsedQuery = [];
        parse_str($urlParts[1], $parsedQuery);

        $parsedQuery['callback'] =  $encodedUrl;


        $queryArray = [];
        foreach($parsedQuery as $key => $value) {
            $queryArray[] = $key . '=' . $value;
        }
        $redirectUrl = $urlParts[0] . '?' . implode('&', $queryArray);

        $redirect->setUrl($redirectUrl);
        return $redirect;
    }
}
