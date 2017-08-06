<?php

namespace Rissc\Printformer\Block\Adminhtml\System\Catalog\Product\Form\Renderer;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Model\ProductFactory as CatalogProductFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Form\Element\MultiSelect as OrigMultiselect;
use Rissc\Printformer\Model\ProductFactory as PrintformerProductFactory;
use Rissc\Printformer\Model\Product as PrintformerProduct;
use Rissc\Printformer\Model\ResourceModel\Product\Collection;
use Rissc\Printformer\Gateway\User\Draft as GatewayDraft;

class MultiSelect extends OrigMultiselect
{
    /**
     * @var CatalogProductFactory
     */
    protected $_catalogProductFactory;

    /**
     * @var PrintformerProductFactory
     */
    protected $_printformerProductFactory;

    /**
     * @var GatewayDraft
     */
    protected $_gatewayDraft;

    /**
     * MultiSelect constructor.
     * @param ContextInterface $context
     * @param CatalogProductFactory $catalogProductFactory
     * @param PrintformerProductFactory $printformerProductFactory
     * @param GatewayDraft $gatewayDraft
     * @param null $options
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        CatalogProductFactory $catalogProductFactory,
        PrintformerProductFactory $printformerProductFactory,
        GatewayDraft $gatewayDraft,
        $options = null,
        array $components = [],
        array $data = []
    ) {
        $this->_catalogProductFactory = $catalogProductFactory;
        $this->_printformerProductFactory = $printformerProductFactory;
        $this->_gatewayDraft = $gatewayDraft;
        parent::__construct($context, $options, $components, $data);
    }

    public function prepare()
    {
        parent::prepare();

        if($this->getData('name') == 'printformer_capabilities') {
            $deleteAll = false;
            $productId = $this->context->getRequestParam('id');
            $config = $this->getData('config');
            /** @var CatalogProduct $product */
            $product = $this->_catalogProductFactory->create();
            $product->getResource()->load($product, $productId);

            if($product->getId()) {
                $printformerMasterId = $product->getData('printformer_product');

                /** @var PrintformerProduct $printformerProduct */
                $printformerProduct = $this->_printformerProductFactory->create();
                /** @var Collection $printformerCollection */
                $printformerCollection = $printformerProduct->getCollection()
                    ->addFieldToFilter('master_id', ['eq' => $printformerMasterId]);

                $printformerProduct = $printformerCollection->getFirstItem();

                if($printformerProduct->getId()) {
                    if($printformerProduct->getIntents()) {
                        $intents = explode(',', $printformerProduct->getIntents());
                        $mappedIntents = [];
                        foreach($config['options'] as $value) {
                            if($value['value'] != '') {
                                foreach($intents as $intent) {
                                    if($this->_gatewayDraft->getIntent($value['label']) == $intent) {
                                        $mappedIntents[$value['value']] = [
                                            'label' => $value['label'],
                                            'identifier' => $intent
                                        ];
                                    }
                                }
                            }
                        }

                        foreach($config['options'] as $key => $value) {
                            if($value['value'] != '') {
                                if(!isset($mappedIntents[$value['value']])) {
                                    unset($config['options'][$key]);
                                }
                            }
                        }
                    } else {
                        $deleteAll = true;
                    }
                } else {
                    $deleteAll = true;
                }

                if($deleteAll) {
                    foreach($config['options'] as $key => $value) {
                        if($value['value'] != '') {
                            unset($config['options'][$key]);
                        }
                    }
                }

                $this->setData('config', $config);
            }
        }
    }
}