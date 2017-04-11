<?php
namespace Rissc\Printformer\Block\Adminhtml;

use \Magento\Backend\Block\Widget\Grid\Container as GridContainer;
use \Magento\Framework\View\Element\BlockInterface;

class Products
    extends GridContainer
    implements BlockInterface
{
    protected function _construct()
    {
        $this->_controller = 'adminhtml_products';
        $this->_blockGroup = 'Rissc_Printformer';
        $this->_headerText = __('Printformer Products');
        parent::_construct();

        $this->removeButton('add');
    }
}