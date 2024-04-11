<?php
namespace Rissc\Printformer\Ui\Modifiers;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\Phrase;
use Magento\Ui\Component\DynamicRows;
use Magento\Ui\Component\Form\Element\DataType\Number;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Modal;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Framework\UrlInterface;
use Rissc\Printformer\Helper\Product as ProductHelper;
use Rissc\Printformer\Helper\Config as ConfigHelper;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Framework\App\ProductMetadata;

/**
 * Class Templates
 * @package Rissc\Printformer\Ui\Modifiers
 */
class Templates implements ModifierInterface
{
    const DATA_SCOPE = 'printformer_templates';
    const DATA_SOURCE_DEFAULT = 'product';
    const DATA_SCOPE_PRODUCT = 'data.product';

    const GROUP_TEMPLATES = 'pftemplates';

    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var string
     */
    protected $scopeName;

    /**
     * @var string
     */
    protected $scopePrefix;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var ProductHelper
     */
    protected $productHelper;

    /**
     * @var BackendSession
     */
    protected $_session;

    /**
     * @var ProductMetadata
     */
    private $productMetadata;

    /**
     * @param LocatorInterface $locator
     * @param UrlInterface $urlBuilder
     * @param ProductHelper $productHelper
     * @param ConfigHelper $config
     * @param BackendSession $session
     * @param ProductMetadata $productMetadata
     * @param string $scopeName
     * @param string $scopePrefix
     */
    public function __construct(
        LocatorInterface $locator,
        UrlInterface $urlBuilder,
        ProductHelper $productHelper,
        ConfigHelper $config,
        BackendSession $session,
        ProductMetadata $productMetadata,
        $scopeName = '',
        $scopePrefix = ''
    ) {
        $this->locator = $locator;
        $this->urlBuilder = $urlBuilder;
        $this->scopeName = $scopeName;
        $this->scopePrefix = $scopePrefix;
        $this->configHelper = $config;
        $this->productHelper = $productHelper;
        $this->_session = $session;
        $this->productMetadata = $productMetadata;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function modifyData(array $data)
    {
        $storeId = $this->locator->getProduct()->getStoreId();
        $productId = $this->locator->getProduct()->getId();

        if (!$this->configHelper->isEnabled($storeId)) {
            return $data;
        }

        $data[$productId]['printformer'][self::DATA_SCOPE] = [];
        $this->_session->setPrintformerTemplatesStoreId($storeId);
        foreach ($this->productHelper->getCatalogProductPrintformerProductsArray($productId, $storeId) as $template) {
            $data[$productId]['printformer'][self::DATA_SCOPE][] = $this->fillData($template);
        }

        $data[$productId][self::DATA_SOURCE_DEFAULT]['current_product_id'] = $productId;
        $data[$productId][self::DATA_SOURCE_DEFAULT]['current_store_id'] = $this->locator->getStore()->getId();

        return $data;
    }

    /**
     * @param array $template
     *
     * @return array
     */
    protected function fillData(array $template)
    {
        return [
            'id' => $template['printformer_product_id'],
            'template_id' => $template['printformer_product_id'],
            'name' => $template['name'],
            'identifier' => $template['identifier'],
            'intent' => $template['intent']
        ];
    }

    /**
     * @param array $meta
     *
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $meta = array_replace_recursive(
            $meta,
            [
                self::GROUP_TEMPLATES => [
                    'children' => [
                        self::DATA_SCOPE => $this->getFieldSet()
                    ],
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => __('Printformer Templates'),
                                'collapsible' => true,
                                'componentType' => Fieldset::NAME,
                                'dataScope' => '',
                                'sortOrder' => 999
                            ],
                        ],

                    ],
                ],
            ]
        );

        return $meta;
    }

    /**
     * @return array
     */
    protected function getFieldSet()
    {
        $content = __('Add printformer templates to current product.');

        return [
            'children' => [
                'button_set' => $this->getButtonSet(
                    $content,
                    __('Add Printformer Template'),
                    self::DATA_SCOPE
                ),
                'modal' => $this->getGenericModal(
                    __('Add Printformer Template'),
                    self::DATA_SCOPE
                ),
                self::DATA_SCOPE => $this->getGrid(self::DATA_SCOPE),
            ],
            'arguments' => [
                'data' => [
                    'config' => [
                        'additionalClasses' => 'admin__fieldset-section',
                        'label' => __('Printformer Templates'),
                        'collapsible' => false,
                        'componentType' => Fieldset::NAME,
                        'dataScope' => '',
                        'sortOrder' => 500,
                    ],
                ],
            ]
        ];
    }

    /**
     * @return bool
     */
    public function isMagentoVersion24(): bool
    {
        if (strpos($this->productMetadata->getVersion(), '2.4') !== false) {
            $result = true;
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * @param Phrase $title
     * @param $scope
     *
     * @return array
     */
    protected function getGenericModal(Phrase $title, $scope)
    {
        $listingTarget = $scope . '_listing';

        $modal = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Modal::NAME,
                        'dataScope' => '',
                        'component' => 'Rissc_Printformer/component/templates_modal',
                        'syncUrl'   => $this->urlBuilder->getUrl('printformer/product/sync', ['store_id' => $this->locator->getProduct()->getStoreId()]),
                        'options' => [
                            'title' => $title,
                            'buttons' => [
                                [
                                    'text' => __('Cancel'),
                                    'actions' => [
                                        'closeModal'
                                    ]
                                ],
                                [
                                    'text' => __('Synchronize templates'),
                                    'actions' => [
                                        'synchronizeTemplates'
                                    ]
                                ],
                                [
                                    'text' => __('Add Selected Templates'),
                                    'class' => 'action-primary',
                                    'actions' => [
                                        [
                                            'targetName' => 'index = ' . $listingTarget,
                                            'actionName' => 'save'
                                        ],
                                        'closeModal'
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'children' => [
                $listingTarget => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'autoRender' => false,
                                'componentType' => 'insertListing',
                                'dataScope' => $listingTarget,
                                'externalProvider' => $listingTarget . '.' . $listingTarget . '_data_source',
                                'selectionsProvider' => $listingTarget . '.' . $listingTarget . '.printformer_templates_columns.ids',
                                'ns' => $listingTarget,
                                'render_url' => $this->urlBuilder->getUrl('mui/index/render'),
                                'realTimeLink' => true,
                                'dataLinks' => [
                                    'imports' => false,
                                    'exports' => true
                                ],
                                'behaviourType' => 'simple',
                                'externalFilterMode' => true,
                                'imports' => [
                                    'productId' => '${ $.provider }:data.product.current_product_id',
                                    'storeId' => '${ $.provider }:data.product.current_store_id',
                                ],
                                'exports' => [
                                    'productId' => '${ $.externalProvider }:params.current_product_id',
                                    'storeId' => '${ $.externalProvider }:params.current_store_id',
                                ]
                            ],
                        ],
                    ],
                ],
            ],
        ];

        if ($this->isMagentoVersion24()) {
            $modal['children'][$listingTarget]['arguments']['data']['config']['imports']['__disableTmpl'] = ['productId' => false, 'storeId' => false];
            $modal['children'][$listingTarget]['arguments']['data']['config']['exports']['__disableTmpl'] = ['productId' => false, 'storeId' => false];
        }

        return $modal;
    }

    /**
     * @param Phrase $content
     * @param Phrase $buttonTitle
     * @param $scope
     *
     * @return array
     */
    protected function getButtonSet(Phrase $content, Phrase $buttonTitle, $scope)
    {
        $modalTarget = $this->scopeName . '.' . self::GROUP_TEMPLATES . '.' . $scope . '.modal';

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => 'container',
                        'componentType' => 'container',
                        'label' => false,
                        'content' => $content,
                        'template' => 'ui/form/components/complex',
                    ],
                ],
            ],
            'children' => [
                'button_' . $scope => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'formElement' => 'container',
                                'componentType' => 'container',
                                'component' => 'Magento_Ui/js/form/components/button',
                                'actions' => [
                                    [
                                        'targetName' => $modalTarget,
                                        'actionName' => 'toggleModal',
                                    ],
                                    [
                                        'targetName' => $modalTarget . '.' . $scope . '_listing',
                                        'actionName' => 'render',
                                    ]
                                ],
                                'title' => $buttonTitle,
                                'provider' => null,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Retrieve grid
     *
     * @param string $scope
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @since 101.0.0
     */
    protected function getGrid($scope)
    {
        $dataProvider = $scope . '_listing';

        $result = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'additionalClasses' => 'admin__field-wide',
                        'componentType' => DynamicRows::NAME,
                        'label' => null,
                        'columnsHeader' => false,
                        'columnsHeaderAfterRender' => true,
                        'renderDefaultRecord' => false,
                        'template' => 'ui/dynamic-rows/templates/grid',
                        'component' => 'Magento_Ui/js/dynamic-rows/dynamic-rows-grid',
                        'addButton' => false,
                        'recordTemplate' => 'record',
                        'dataScope' => 'data.printformer',
                        'deleteButtonLabel' => __('Remove'),
                        'dataProvider' => $dataProvider,
                        'map' => [
                            'id' => 'template_id',
                            'template_id' => 'template_id',
                            'name' => 'name',
                            'identifier' => 'identifier',
                            'intent' => 'intent'
                        ],
                        'links' => [
                            'insertData' => '${ $.provider }:${ $.dataProvider }'
                        ],
                        'sortOrder' => 2,
                    ],
                ],
            ],
            'children' => [
                'record' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => 'container',
                                'isTemplate' => true,
                                'is_collection' => true,
                                'component' => 'Magento_Ui/js/dynamic-rows/record',
                                'dataScope' => '',
                            ],
                        ],
                    ],
                    'children' => $this->fillMeta(),
                ],
            ],
        ];

        if ($this->isMagentoVersion24()) {
            $result['arguments']['data']['config']['links']['__disableTmpl'] = ['insertData' => false];
        }

        return $result;
    }

    /**
     * Retrieve meta column
     *
     * @return array
     * @since 101.0.0
     */
    protected function fillMeta()
    {
        return [
            'template_id' => $this->getTextColumn('template_id', false, __('ID'), 10),
            'name' => $this->getTextColumn('name', false, __('Name'), 20),
            'identifier' => $this->getTextColumn('identifier', true, __('Identifier'), 30),
            'intent' => $this->getTextColumn('intent', true, __('Intent'), 40),
            'actionDelete' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'additionalClasses' => 'data-grid-actions-cell',
                            'componentType' => 'actionDelete',
                            'dataType' => Text::NAME,
                            'label' => __('Actions'),
                            'sortOrder' => 70,
                            'fit' => true,
                        ],
                    ],
                ],
            ],
            'position' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'dataType' => Number::NAME,
                            'formElement' => Input::NAME,
                            'componentType' => Field::NAME,
                            'dataScope' => 'position',
                            'sortOrder' => 80,
                            'visible' => false,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Retrieve text column structure
     *
     * @param string $dataScope
     * @param bool $fit
     * @param Phrase $label
     * @param int $sortOrder
     * @return array
     * @since 101.0.0
     */
    protected function getTextColumn($dataScope, $fit, Phrase $label, $sortOrder)
    {
        $column = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Field::NAME,
                        'formElement' => Input::NAME,
                        'elementTmpl' => 'ui/dynamic-rows/cells/text',
                        'component' => 'Magento_Ui/js/form/element/text',
                        'dataType' => Text::NAME,
                        'dataScope' => $dataScope,
                        'fit' => $fit,
                        'label' => $label,
                        'sortOrder' => $sortOrder,
                    ],
                ],
            ],
        ];

        return $column;
    }
}