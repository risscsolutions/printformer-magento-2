<?php
namespace Rissc\Printformer\Block\Adminhtml\System\Config\Form\Field;

class OptionMap extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $_elementFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        array $data = []
    )
    {
        $this->_elementFactory  = $elementFactory;
        parent::__construct($context, $data);
    }

    /* (non-PHPdoc)
     * @see \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray::_construct()
     */
    protected function _construct()
    {
        $this->addColumn('option', ['label' => __('Option')]);
        $this->addColumn('value', ['label' => __('Value')]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
        parent::_construct();
    }
}
