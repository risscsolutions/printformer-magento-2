<?php

namespace Rissc\Printformer\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\Data\Form\Element\Factory;

class AttributeMap extends AbstractFieldArray
{
    protected $_template = 'Rissc_Printformer::system/config/form/field/attributemap.phtml';

    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $_elementFactory;

    /**
     * @var string
     */
    protected $_defaultStoreVarName = 'store';

    /**
     * AttributeMap constructor.
     *
     * @param   Context  $context
     * @param   Factory  $elementFactory
     * @param   array    $data
     */
    public function __construct(
        Context $context,
        Factory $elementFactory,
        array $data = []
    ) {
        $this->_elementFactory = $elementFactory;
        parent::__construct($context, $data);
    }

    /**
     * Render array cell for prototypeJS template
     *
     * @param   string  $columnName
     *
     * @return string
     * @throws \Exception
     */
    public function renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            throw new \Exception('Wrong column name specified.');
        }
        $column    = $this->_columns[$columnName];
        $inputName = $this->_getCellInputElementName($columnName);

        if ($column['renderer']) {
            return $column['renderer']->setInputName(
                $inputName
            )->setInputId(
                $this->_getCellInputElementId('<%- _id %>', $columnName)
            )->setColumnName(
                $columnName
            )->setColumn(
                $column
            )->toHtml();
        }

        $html      = '';
        $inputType = 'hidden';
        if ($columnName == 'value') {
            $inputType = 'text';
        } else {
            $html .= '<span>';
            $html .= '<%- '.$columnName.' %>';
            $html .= '</span>';
        }

        $html .= '<input type="'.$inputType.'" id="'
            .$this->_getCellInputElementId(
                '<%- _id %>',
                $columnName
            ).
            '"'.
            ' name="'.
            $inputName.
            '" value="<%- '.
            $columnName.
            ' %>" '.
            ($column['size'] ? 'size="'.
                $column['size'].
                '"' : '').
            ' class="'.
            (isset(
                $column['class']
            ) ? $column['class'] : 'input-text').'"'.(isset(
                $column['style']
            ) ? ' style="'.$column['style'].'"' : '').'/>';

        return $html;
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->addColumn('attr_id', ['label' => __('ID')]);
        $this->addColumn('attribute', ['label' => __('Option')]);
        $this->addColumn('value', ['label' => __('Value')]);
        $this->_addAfter       = false;
        $this->_addButtonLabel = __('Add');

        $this->setData('ajax_url_template',
            $this->_urlBuilder->getUrl('printformer/product/attribute', [
                'form_key' => $this->getFormKey(),
                'store_id' => $this->getCurrentStoreId(),
                'code'     => '%code%',
            ])
        );

        parent::_construct();
    }

    /**
     * @return int|null
     */
    public function getCurrentStoreId()
    {
        if (!$this->hasData('store_id')) {
            $this->setData('store_id',
                (int)$this->getRequest()->getParam($this->getStoreVarName()));
        }

        return $this->getData('store_id');
    }

    /**
     * @return mixed|string
     */
    public function getStoreVarName()
    {
        if ($this->hasData('store_var_name')) {
            return (string)$this->getData('store_var_name');
        } else {
            return (string)$this->_defaultStoreVarName;
        }
    }
}
