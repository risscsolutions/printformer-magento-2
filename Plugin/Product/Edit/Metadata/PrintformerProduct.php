<?php

namespace Rissc\Printformer\Plugin\Product\Edit\Metadata;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\DynamicRows;
use Magento\Ui\Component\Form\Element\ActionDelete;
use Magento\Ui\Component\Modal;
use Rissc\Printformer\Helper\Product;
use Rissc\Printformer\Ui\DataProvider\Product\PrintformerProductDataProvider;

class PrintformerProduct
{
    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var PrintformerProductDataProvider
     */
    protected $dataProvider;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var Product
     */
    protected $productHelper;

    /**
     * @var string
     */
    protected $scopeName = 'product_form.product_form';

    /**
     * PrintformerProduct constructor.
     * @param Product $productHelper
     * @param PrintformerProductDataProvider $dataProvider
     * @param UrlInterface $urlBuilder
     * @param RequestInterface $request
     * @param LocatorInterface $locator
     */
    public function __construct(
        Product $productHelper,
        PrintformerProductDataProvider $dataProvider,
        UrlInterface $urlBuilder,
        RequestInterface $request,
        LocatorInterface $locator
    ) {
        $this->productHelper = $productHelper;
        $this->dataProvider = $dataProvider;
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
        $this->locator = $locator;
    }

    /**
     * @param CustomOptions $subject
     * @param $meta
     * @return mixed
     */
    public function afterModifyMeta(CustomOptions $subject, $meta)
    {
        if($this->request->getModuleName() != 'catalog' && $this->request->getActionName() != 'product') {
            return $meta;
        }

        $meta['printformer_products'] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => 'fieldset',
                        'label' => __('Printformer'),
                        'collapsible' => true,
                        'dataScope' => 'data.product',
                        'sortOrder' => 1000
                    ]
                ]
            ],
            'children' => [
                'button_set' => $this->getButtonSet(
                    __('Add printformer templates to current product.'),
                    __('Add Printformer Templates'),
                    'printformer_products'
                ),
                'modal' => $this->getGenericModal(
                    __('Add Printformer Templates')
                ),
                'printformer_products' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'dataType' => 'text',
                                'formElement' => 'input',
                                'visible' => true,
                                'required' => false,
                                'notice' => '',
                                'default' => '',
                                'label' => '',
                                'code' => 'test',
                                'source' => 'test',
                                'sortOrder' => 0,
                                'componentType' => DynamicRows::NAME,
                                'component' => 'Magento_Ui/js/dynamic-rows/dynamic-rows',
                                'addButton' => false,
                                'additionalClasses' => 'admin__field-wide',
                                'deleteProperty' => 'is_delete',
                                'deleteValue' => '1',
                                'renderDefaultRecord' => false
                            ]
                        ]
                    ],
                    'children' => [
                        'record' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'componentType' => 'container',
                                        'component' => 'Magento_Ui/js/dynamic-rows/record',
                                        'positionProvider' => 'sort_order',
                                        'isTemplate' => true,
                                        'is_collection' => true,
                                    ],
                                ],
                            ],
                            'children' => [
                                'id' => $this->getFieldConfig(__('ID'), 10, 'id'),
                                'name' => $this->getFieldConfig(__('Name'), 20, 'name'),
                                'master_id' => $this->getFieldConfig(__('Master ID'), 30, 'master_id'),
                                'intent' => $this->getFieldConfig(__('Intent'), 40, 'intent'),
                                'is_delete' => $this->getIsDeleteFieldConfig(100)
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $meta;
    }

    /**
     * @param CustomOptions $subject
     * @param $meta
     * @return mixed
     */
    public function afterModifyData(CustomOptions $subject, $meta)
    {
        if($this->request->getModuleName() != 'catalog' && $this->request->getActionName() != 'product') {
            return $meta;
        }

        $productId = $this->locator->getProduct()->getId();
        $storeId = $this->locator->getProduct()->getStoreId();
        $printformerProducts = [];
        $i = 0;
        foreach($this->productHelper->getCatalogProductPrintformerProductsArray($productId, $storeId) as $product) {
            $printformerProducts[$i] = $product;
            $printformerProducts[$i]['record_id'] = $i;
            $i++;
        }
        $meta[$productId]['product']['printformer_products'] = $printformerProducts;
        return $meta;
    }

    protected function getButtonSet(Phrase $content, Phrase $buttonTitle, $scope)
    {
        $modalTarget = $this->scopeName . '.printformer_products.modal';

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
                                        'targetName' => $modalTarget . '.printformer_product_listing',
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

    protected function getGenericModal(Phrase $title)
    {
        $listingTarget = 'printformer_product_listing';

        $modal = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Modal::NAME,
                        'dataScope' => '',
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
                                'component' => 'Rissc_Printformer/component/insert-listing',
                                'componentType' => 'insertListing',
                                'printformerProducts' => $this->dataProvider->getData(),
                                'dataScope' => $listingTarget,
                                'externalProvider' => $listingTarget . '.' . $listingTarget . '_data_source',
                                'selectionsProvider' => $listingTarget . '.' . $listingTarget . '.printformer_product_columns.ids',
                                'indexField' => 'id',
                                'ns' => $listingTarget,
                                'render_url' => $this->urlBuilder->getUrl('mui/index/render'),
                                'realTimeLink' => true,
                                'provider' =>
                                    'product_form.product_form_data_source',
                                'dataLinks' => [
                                    'imports' => false,
                                    'exports' => true
                                ],
                                'behaviourType' => 'simple',
                                'externalFilterMode' => true,
                                'imports' => [
                                    'storeId' => '${ $.provider }:data.product.current_store_id',
                                ],
                                'exports' => [
                                    'storeId' => '${ $.externalProvider }:params.current_store_id',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $modal;
    }

    protected function getFieldConfig($label, $sortOrder, $scope, array $options = [])
    {
        return array_replace_recursive(
            [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => $label,
                            'componentType' => 'field',
                            'formElement' => 'input',
                            'dataScope' => $scope,
                            'dataType' => 'text',
                            'sortOrder' => $sortOrder,
                            'disabled' => true
                        ],
                    ],
                ],
            ],
            $options
        );
    }

    protected function getIsDeleteFieldConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => ActionDelete::NAME,
                        'fit' => true,
                        'sortOrder' => $sortOrder
                    ],
                ],
            ],
        ];
    }
}