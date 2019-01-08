<?php

namespace Rissc\Printformer\Block\Adminhtml\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class PrintformerIdentifier extends Generic implements TabInterface
{
    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;

    /**
     * Crossmedia constructor.
     * @param CustomerFactory $customerFactory
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        CustomerFactory $customerFactory,
        array $data = []
    ) {
        $this->_customerFactory = $customerFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Whether tab is available
     *
     * @return bool
     */
    public function canShowTab()
    {
        return $this->_scopeConfig->isSetFlag(\Rissc\Printformer\Helper\Config::XML_PATH_CONFIG_ENABLED);
    }

    /**
     * Get tab label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Printformer');
    }

    /**
     * Get tab title
     *
     * @return string
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * Whether tab is visible
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Return URL link to Tab content
     *
     * @return string
     */
    public function getTabUrl()
    {
        return $this->getUrl('printformer/customer/printformeridentification', ['_current' => true]);
    }
    /**
     * Tab should be loaded trough Ajax call
     *
     * @return bool
     */
    public function isAjaxLoaded()
    {
        return false;
    }

    /**
     * @return \Magento\Customer\Model\Customer
     */
    public function getCustomer()
    {
        $customerId = $this->getRequest()->getParam('id');
        return $this->_customerFactory->create()->load($customerId);
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initForm()
    {
        if (!$this->canShowTab()) {
            return $this;
        }

        $customer = $this->getCustomer();

        /**@var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('myform_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Printformer')]);
        $disabled = true;

        $printformerIdentification = $customer->getResource()->getAttribute('printformer_identification')->getFrontend()->getValue($customer);

        $fieldset->addField(
            'test',
            'text',
            [
                'label' => __('Printformer Identification'),
                'required' => false,
                'name' => 'printformer_identification',
                'disabled' => $disabled,
                'value' =>  ($printformerIdentification === null || $printformerIdentification == '') ? 'Nicht gesetzt' : $printformerIdentification
            ]
        );

        $this->setForm($form);

        return $this;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _toHtml()
    {
        if ($this->canShowTab()) {
            $this->initForm();
            return parent::_toHtml();
        } else {
            return '';
        }
    }
    /**
     * Prepare the layout.
     *
     * @return string
     */
    public function getFormHtml()
    {
        $html = parent::getFormHtml();
        return $html;
    }
}