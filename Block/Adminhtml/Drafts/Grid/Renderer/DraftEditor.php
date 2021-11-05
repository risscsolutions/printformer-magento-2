<?php

namespace Rissc\Printformer\Block\Adminhtml\Drafts\Grid\Renderer;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Backend\Model\UrlInterface;
use Rissc\Printformer\Model\Draft;
use Rissc\Printformer\Helper\Config;

class DraftEditor extends AbstractRenderer
{
    /**
     * @var UrlInterface
     */
    protected $_url;

    /** @var Config */
    protected $_config;

    /**
     * @param Context $context
     * @param UrlInterface $url
     * @param array $data
     */
    public function __construct(
        Context $context,
        UrlInterface $url,
        array $data = []
    ) {
        $this->_url = $url;

        parent::__construct($context, $data);
    }

    /**
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        /** @var Draft $row */
        $html = '';
        if($draftId = $row->getDraftId()) {
            $html .= '<div><span>' . __('Open Editor') . ':</span><br />';
            $html .= '<a href="' . $this->getEditorUrl($row) . '" target="_blank">';
            $html .= $row->getDraftId();
            $html .= '</a></div>';
        }

        return $html;
    }

    /**
     * @param DataObject $row
     * @return string
     */
    protected function getEditorUrl(DataObject $row)
    {
        $url = $this->_url->getUrl(
            'printformer/editor/webtoken',
            [
                'draft_id' => $row->getDraftId(),
                'order_item_Id' => $row->getOrderItemId(),
                'store_id' => $row->getStoreId()
            ]
        );

        return $url;
    }
}