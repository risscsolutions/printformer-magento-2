<?php
namespace Rissc\Printformer\Block\Adminhtml\System\Config\Form\Button;

use Rissc\Printformer\Block\Adminhtml\System\Config\Form\Button;

class Sync extends Button
{
    /**
     * @var string
     */
    protected $_defaultStoreVarName = 'store';

    /**
     * @var string
     */
    protected $_buttonLabel = 'Synchronize templates';

    /**
     * @return int|null
     */
    public function getCurrentStoreId()
    {
        if (!$this->hasData('store_id')) {
            $this->setData('store_id', (int)$this->getRequest()->getParam($this->getStoreVarName()));
        }
        return $this->getData('store_id');
    }

    /**
     * @return string
     */
    public function getStoreVarName()
    {
        if ($this->hasData('store_var_name')) {
            return (string)$this->getData('store_var_name');
        } else {
            return (string)$this->_defaultStoreVarName;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('Rissc_Printformer::system/config/form/button/sync.phtml');
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $buttonLabel = !empty($originalData['button_label']) ? $originalData['button_label'] : $this->_buttonLabel;
        $this->addData(
            [
                'button_label' => __($buttonLabel),
                'html_id'      => $element->getHtmlId(),
                'ajax_url'     => $this->_urlBuilder->getUrl('printformer/product/sync',
                    ['store_id' => $this->getCurrentStoreId()]
                ),
            ]
        );
        return $this->_toHtml();
    }
}
