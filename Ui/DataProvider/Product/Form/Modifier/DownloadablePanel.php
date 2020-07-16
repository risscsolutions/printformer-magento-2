<?php
namespace Rissc\Printformer\Ui\DataProvider\Product\Form\Modifier;

use Magento\Downloadable\Api\Data\ProductAttributeInterface;
use Magento\Downloadable\Ui\DataProvider\Product\Form\Modifier\DownloadablePanel as ParentDownloadablePanel;
use Magento\Ui\Component\Form;
use Magento\Downloadable\Ui\DataProvider\Product\Form\Modifier\Composite;

/**
 * Class DownloadablePanel
 * @package Rissc\Printformer\Ui\DataProvider\Product\Form\Modifier
 */
class DownloadablePanel extends ParentDownloadablePanel
{
    /**
     * Add Checkbox
     *
     * @return void
     */
    protected function addCheckboxIsDownloadable()
    {
        $checkboxPath = Composite::CHILDREN_PATH . '/' . ProductAttributeInterface::CODE_IS_DOWNLOADABLE;
        $checkboxConfig['arguments']['data']['config'] = [
            'dataType' => Form\Element\DataType\Number::NAME,
            'formElement' => Form\Element\Checkbox::NAME,
            'componentType' => Form\Field::NAME,
            'component' => 'Rissc_Printformer/js/components/is-downloadable-handler',
            'description' => __('Is this downloadable Product?'),
            'dataScope' => ProductAttributeInterface::CODE_IS_DOWNLOADABLE,
            'sortOrder' => 10,
            'imports' => [
                'disabled' => '${$.provider}:' . self::DATA_SCOPE_PRODUCT . '.'
                    . ProductAttributeInterface::CODE_HAS_WEIGHT
            ],
            'valueMap' => [
                'false' => '0',
                'true' => '1',
            ],
            'samplesFieldset' => 'ns = ${ $.ns }, index=' . Composite::CONTAINER_SAMPLES,
            'linksFieldset' => 'ns = ${ $.ns }, index=' . Composite::CONTAINER_LINKS,
            'printformerFieldset' => 'ns = ${ $.ns }, index=' . Printformer::CONTAINER_PRINTFORMER
        ];

        $this->meta = $this->arrayManager->set($checkboxPath, $this->meta, $checkboxConfig);
    }
}
