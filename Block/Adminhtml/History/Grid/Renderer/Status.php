<?php

namespace Rissc\Printformer\Block\Adminhtml\History\Grid\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Backend\Block\Context;

/**
 * Class Status
 * @package Rissc\Printformer\Block\Adminhtml\History\Grid\Renderer
 */
class Status
    extends AbstractRenderer
{
    /**
     * @param \Magento\Framework\DataObject $row
     *
     * @return string
     */
    public function render(DataObject $row)
    {
        /** @var \Rissc\Printformer\Model\History\Log $row */
        return __($row->getStatus());
    }
}