<?php

declare(strict_types=1);

namespace Rissc\Printformer\Setup\Patch\Schema;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;

/**
 * printformer_product has a composite PK (id, store_id), but
 * catalog_product_printformer_product references only `id`.
 * MySQL 8.4+ requires the referenced column to have its own unique key.
 */
class AddPrintformerProductIdUniqueKey implements SchemaPatchInterface
{
    private const TABLE_NAME = 'printformer_product';

    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        $table = $this->moduleDataSetup->getTable(self::TABLE_NAME);

        if (!$this->hasUniqueIdIndex($table)) {
            $connection->addIndex(
                $table,
                $connection->getIndexName($table, ['id'], AdapterInterface::INDEX_TYPE_UNIQUE),
                ['id'],
                AdapterInterface::INDEX_TYPE_UNIQUE
            );
        }

        $connection->endSetup();

        return $this;
    }

    private function hasUniqueIdIndex(string $table): bool
    {
        $indexes = $this->moduleDataSetup->getConnection()->getIndexList($table);
        foreach ($indexes as $index) {
            if (strtolower((string)$index['INDEX_TYPE']) === AdapterInterface::INDEX_TYPE_UNIQUE
                && $index['COLUMNS_LIST'] === ['id']
            ) {
                return true;
            }
        }

        return false;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}