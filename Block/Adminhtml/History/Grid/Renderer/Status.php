<?php

namespace Rissc\Printformer\Block\Adminhtml\History\Grid\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

class Status extends AbstractRenderer
{
    /**
     * @param DataObject $row
     * @return \Magento\Framework\Phrase
     */
    public function render(DataObject $row)
    {
        /** @var \Rissc\Printformer\Model\History\Log $row */
        return __($row->getStatus());
    }
}