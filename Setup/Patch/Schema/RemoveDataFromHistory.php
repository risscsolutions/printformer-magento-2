<?php
namespace Rissc\Printformer\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Drops request_data and response_data columns from printformer_async_history.
 * These columns stored API keys / JWTs and untrusted callback payloads.
 */
class RemoveDataFromHistory implements SchemaPatchInterface
{
    private $schemaSetup;

    public function __construct(SchemaSetupInterface $schemaSetup)
    {
        $this->schemaSetup = $schemaSetup;
    }

    public function apply()
    {
        $setup = $this->schemaSetup;
        $setup->startSetup();

        $connection = $setup->getConnection();
        $table = $setup->getTable('printformer_async_history');

        foreach (['request_data', 'response_data'] as $column) {
            if ($connection->tableColumnExists($table, $column)) {
                $connection->dropColumn($table, $column);
            }
        }

        $setup->endSetup();
        return $this;
    }

    public static function getDependencies() { return []; }

    public function getAliases() { return []; }
}
