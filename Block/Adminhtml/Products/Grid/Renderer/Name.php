<?php

namespace Rissc\Printformer\Block\Adminhtml\Products\Grid\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

class Name extends AbstractRenderer
{
    public function render(DataObject $row)
    {
        return $row->getName();
    }
}