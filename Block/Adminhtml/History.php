<?php

namespace Rissc\Printformer\Block\Adminhtml;

use \Magento\Backend\Block\Widget\Grid\Container as GridContainer;

/**
 * Class History
 * @package Rissc\Printformer\Block\Adminhtml
 */
class History
    extends GridContainer
{
    protected function _construct()
    {
        $this->_controller = 'adminhtml_history';
        $this->_blockGroup = 'Rissc_Printformer';
        $this->_headerText = __('Printformer Processing History');
        parent::_construct();

        $this->removeButton('add');
    }

    public function toHtml()
    {
        /** @var History\Grid\Info $infoBlock */
        $infoBlock = $this->getLayout()->createBlock('Rissc\Printformer\Block\Adminhtml\History\Grid\Info');
        $infoBlock->setTemplate('Rissc_Printformer::history/grid/info.phtml');
        return parent::toHtml() . $infoBlock->toHtml();
    }
}