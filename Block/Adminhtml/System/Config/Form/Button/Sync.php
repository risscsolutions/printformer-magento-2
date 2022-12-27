<?php

namespace Rissc\Printformer\Block\Adminhtml\System\Config\Form\Button;

use Magento\Backend\Block\Template\Context;
use Magento\Store\Model\WebsiteRepository;
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
     * @var WebsiteRepository
     */
    protected $_websiteRepository;

    public function __construct(
        Context $context,
        WebsiteRepository $websiteRepository,
        array $data = []
    ) {
        $this->_websiteRepository = $websiteRepository;

        parent::__construct($context, $data);
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
    protected function _getElementHtml(
        \Magento\Framework\Data\Form\Element\AbstractElement $element
    ) {
        $originalData = $element->getOriginalData();
        $buttonLabel  = !empty($originalData['button_label'])
            ? $originalData['button_label'] : $this->_buttonLabel;
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

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrentStoreId()
    {
        if ($this->getRequest()->getParam('website')) {
            $website = $this->_websiteRepository->getById($this->getRequest()
                ->getParam('website'));
            $this->setData('store_id', $website->getDefaultStore()->getId());
        }

        if (!$this->hasData('store_id')) {
            $this->setData('store_id',
                (int)$this->getRequest()->getParam($this->getStoreVarName()));
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
}
