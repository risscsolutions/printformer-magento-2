<?php
namespace Rissc\Printformer\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    const TABLE_NAME_DRAFT    = 'printformer_draft';

    const TABLE_NAME_PRODUCT  = 'printformer_product';
    const COLUMN_NAME_DRAFTID = 'printformer_draftid';
    const COLUMN_NAME_STOREID = 'printformer_storeid';
    const COLUMN_NAME_ORDERED = 'printformer_ordered';

    /* (non-PHPdoc)
     * @see \Magento\Framework\Setup\InstallSchemaInterface::install()
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $setup->getConnection()->addColumn(
            $setup->getTable('quote_item'),
            self::COLUMN_NAME_DRAFTID,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 32,
                'nullable' => true,
                'comment' => 'Printformer Draft ID'
            ]
        );

        $setup->getConnection()->addColumn(
            $setup->getTable('quote_item'),
            self::COLUMN_NAME_STOREID,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'unsigned' => true,
                'nullable' => false,
                'comment' => 'Printformer Store ID'
            ]
        );

        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order_item'),
            self::COLUMN_NAME_DRAFTID,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 32,
                'nullable' => true,
                'comment' => 'Printformer Draft ID'
            ]
        );

        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order_item'),
            self::COLUMN_NAME_STOREID,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'unsigned' => true,
                'nullable' => false,
                'comment' => 'Printformer Store ID'
            ]
        );

        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order_item'),
            self::COLUMN_NAME_ORDERED,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'unsigned' => true,
                'nullable' => false,
                'default' => '0',
                'comment' => 'Printformer Ordered'
            ]
        );

        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::TABLE_NAME_PRODUCT)
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            array (
                'identity' => true,
                'nullable' => false,
                'primary' => true,
                'unsigned' => true
            ),
            'Product ID'
        )->addColumn(
            'store_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            [
                'unsigned' => true,
                'nullable' => false,
                'primary' => true
            ],
            'Store ID'
        )->addIndex(
            $installer->getIdxName(self::TABLE_NAME_PRODUCT, ['store_id']),
            ['store_id']
        )->addForeignKey(
            $installer->getFkName(self::TABLE_NAME_PRODUCT, 'store_id', 'store', 'store_id'),
            'store_id',
            $installer->getTable('store'),
            'store_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->addColumn(
            'sku',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [
                'nullable' => false,
                'unique' => false
            ],
            'SKU'
        )->addColumn(
            'name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [
                'nullable' => false
            ],
            'Name'
        )->addColumn(
            'description',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Description'
        )->addColumn(
            'short_description',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Short Description'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            [
                'nullable' => false,
                'default' => '0',
            ],
            'Status'
        )->addColumn(
            'master_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Master ID'
        )->addColumn(
            'md5',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'md5 hash'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => false,
                'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT
            ],
            'Create At'
        );
        $installer->getConnection()->createTable($table);

        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::TABLE_NAME_DRAFT)
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            array (
                'identity' => true,
                'nullable' => false,
                'primary' => true,
                'unsigned' => true
            ),
            'Draft ID'
        )->addColumn(
            'draft_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Draft Hash'
        )->addColumn(
            'format_variation',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [
                'unsigned' => true
            ],
            'Format Variation'
        )->addColumn(
            'color_variation',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [
                'unsigned' => true
            ],
            'Color Variation'
        )->addColumn(
            'order_item_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [
                'unsigned' => true,
                'nullable' => true,
            ],
            'Order Item ID'
        )->addColumn(
            'store_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            [
                'unsigned' => true,
                'nullable' => false,
                'primary' => true
            ],
            'Store ID'
        )->addColumn(
            'product_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            array (
                'unsigned' => true,
                'nullable' => false,
                'primary' => true
            ),
            'Product ID'
        )->addColumn(
            'qty',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            array (
                'unsigned' => true,
                'nullable' => false,
            ),
            'Qty'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            [
               'nullable' => false,
               'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT
            ],
            'Create At'
        )->addIndex(
            $installer->getIdxName(
                self::TABLE_NAME_DRAFT,
                ['draft_id'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['draft_id'],
            ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
        );

        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
