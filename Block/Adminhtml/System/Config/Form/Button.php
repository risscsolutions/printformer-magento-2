<?php
namespace Rissc\Printformer\Block\Adminhtml\System\Config\Form;

class Button extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @param string $buttonLabel
     * @return \Rissc\Printformer\Block\Adminhtml\System\Config\Form\Button
     */
    public function setbuttonLabel($buttonLabel)
    {
        $this->_buttonLabel = $buttonLabel;
        return $this;
    }

    /* (non-PHPdoc)
     * @see \Magento\Button\Block\System\Button\Form\Field::render()
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }
}
