<?php

namespace Rissc\Printformer\Block\Adminhtml\Products;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Rissc\Printformer\Block\Adminhtml\Products\Grid\Renderer\MagentoProducts;
use Rissc\Printformer\Block\Adminhtml\Products\Grid\Renderer\Name;
use Rissc\Printformer\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Rissc\Printformer\Model\ResourceModel\Catalog\Printformer\Product\CollectionFactory as CatalogCollectionFactory;

class Grid extends Extended
{
    /**
     * @var ProductCollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var CatalogCollectionFactory
     */
    protected $_catalogCollectionFactory;

    /**
     * @var ResourceConnection
     */
    protected $_resourceConnection;

    /**
     * @param Context $context
     * @param Data $backendHelper
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CatalogCollectionFactory $catalogCollectionFactory
     * @param ResourceConnection $resourceConnection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        ProductCollectionFactory $productCollectionFactory,
        CatalogCollectionFactory $catalogCollectionFactory,
        ResourceConnection $resourceConnection,
        array $data = []
    ) {
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_catalogCollectionFactory = $catalogCollectionFactory;
        $this->_resourceConnection = $resourceConnection;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('MasterGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setVarNameFilter('master_filter');
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareCollection()
    {
        $collection = $this->_productCollectionFactory->create();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @return Grid
     * @throws \Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn('id', [
            'header' => __('ID'),
            'index' => 'id',
            'column_css_class' => 'no-display',
            'header_css_class' => 'no-display'

        ]);

        $this->addColumn('master_id', [
            'header' => __('Master-ID'),
            'index' => 'master_id',
        ]);

        $this->addColumn('name', [
            'header' => __('Name'),
            'index' => 'name',
            'type' => 'text',
            'renderer' => Name::class,
        ]);

        $this->addColumn(
            'store_id',
            [
                'header' => __('Store View'),
                'index' => 'store_id',
                'type' => 'store',
                'store_all' => true,
                'store_view' => true,
                'sortable' => false
            ]
        );

        $this->addColumn('intent', [
            'header' => __('Intent'),
            'index' => 'intent',
        ]);

        $this->addColumn(
            'assigned_products',
            [
                'header' => __('Products assigned'),
                'sortable' => false,
                'renderer' => MagentoProducts::class,
                'filter_condition_callback' => [$this, '_filterCollection']
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @param $collection
     * @param $column
     * @return $this
     */
    protected function _filterCollection($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }

        $connection = $this->_resourceConnection->getConnection();
        $select = $connection->select()->from('eav_attribute')->where('attribute_code = ?', 'name')
            ->where('entity_type_id = ?', '4')
            ->limit(1);
        $attributeId = new DataObject($connection->fetchRow($select));

        $catalogCollection = $this->_catalogCollectionFactory->create();
        $catalogCollection->getSelect()
            ->joinInner(
                ['product' => $catalogCollection->getTable('catalog_product_entity_varchar')],
                'product.entity_id = main_table.product_id',
                ['product.value', 'product.entity_id', 'product.store_id']
            )->where('product.attribute_id = ?', $attributeId->getAttributeId())
            ->where('product.store_id = main_table.store_id');
        $catalogCollection->addFieldToFilter('product.value', ['like' => '%' . $value . '%']);

        $ids = [];
        if (empty($catalogCollection)) {
            return $this;
        }

        foreach ($catalogCollection as $item) {
            $ids[] = $item['printformer_product_id'];
        }
        $collection->getSelect()->where('`main_table`.`id` IN (?)', $ids);

        return $this;
    }
}
