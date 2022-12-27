<?php

namespace Rissc\Printformer\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Downloadable\Model\Product\Type;
use Magento\Downloadable\Model\Source\Shareable;
use Magento\Downloadable\Model\Source\TypeUpload;
use Magento\Downloadable\Ui\DataProvider\Product\Form\Modifier\Composite;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Form;


/**
 * Class Printformer
 *
 * @package Rissc\Printformer\Ui\DataProvider\Product\Form\Modifier
 */
class Printformer extends AbstractModifier
{
    const CONTAINER_PRINTFORMER = 'container_printformer';

    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ArrayManager
     */
    protected $arrayManager;

    /**
     * @var TypeUpload
     */
    protected $typeUpload;

    /**
     * @var Shareable
     */
    protected $shareable;

    /**
     * @var Data\Printformer
     */
    protected $printformerData;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * Printformer constructor.
     *
     * @param   LocatorInterface       $locator
     * @param   StoreManagerInterface  $storeManager
     * @param   ArrayManager           $arrayManager
     * @param   UrlInterface           $urlBuilder
     * @param   TypeUpload             $typeUpload
     * @param   Shareable              $shareable
     * @param   Data\Printformer       $printformerData
     */
    public function __construct(
        LocatorInterface $locator,
        StoreManagerInterface $storeManager,
        ArrayManager $arrayManager,
        UrlInterface $urlBuilder,
        TypeUpload $typeUpload,
        Shareable $shareable,
        Data\Printformer $printformerData
    ) {
        $this->locator         = $locator;
        $this->storeManager    = $storeManager;
        $this->arrayManager    = $arrayManager;
        $this->urlBuilder      = $urlBuilder;
        $this->typeUpload      = $typeUpload;
        $this->shareable       = $shareable;
        $this->printformerData = $printformerData;
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data)
    {
        $model = $this->locator->getProduct();

        $data[$model->getId()][self::DATA_SOURCE_DEFAULT]['files_transfer_to_printformer']
            = $this->printformerData->isFilesCanBeTransferredToPrintformer()
            ? '1' : '0';

        return $data;
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function modifyMeta(array $meta)
    {
        $printformerPath = Composite::CHILDREN_PATH.'/'
            .$this::CONTAINER_PRINTFORMER;

        $printformerContainer['arguments']['data']['config'] = [
            'componentType'     => Form\Fieldset::NAME,
            'additionalClasses' => 'admin__fieldset-section',
            'label'             => __('Printformer'),
            'dataScope'         => '',
            'visible'           => $this->locator->getProduct()->getTypeId()
                === Type::TYPE_DOWNLOADABLE,
            'sortOrder'         => 30,
        ];

        $FilesTransferToPrintformer['arguments']['data']['config'] = [
            'componentType' => Form\Field::NAME,
            'formElement'   => Form\Element\Checkbox::NAME,
            'dataType'      => Form\Element\DataType\Number::NAME,
            'description'   => __('Files will be transferred to Printformer'),
            'label'         => ' ',
            'dataScope'     => 'product.files_transfer_to_printformer',
            'scopeLabel'    => $this->storeManager->isSingleStoreMode() ? ''
                : '[GLOBAL]',
            'valueMap'      => [
                'false' => '0',
                'true'  => '1',
            ],
        ];

        $printformerContainer = $this->arrayManager->set(
            'children',
            $printformerContainer,
            [
                'files_transfer_to_printformer' => $FilesTransferToPrintformer,
            ]
        );

        return $this->arrayManager->set($printformerPath, $meta,
            $printformerContainer);
    }
}
