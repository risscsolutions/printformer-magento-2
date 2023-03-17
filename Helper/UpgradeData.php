<?php

namespace Rissc\Printformer\Helper;

use Exception;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Rissc\Printformer\Setup\UpgradeSchema;
use Psr\Log\LoggerInterface;

class UpgradeData extends AbstractHelper
{
    const CATALOG_PRODUCT_WEBSITE_TABLE_NAME = 'catalog_product_website';
    const STORE_TABLE_NAME = 'store';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     * @param Context $context
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        Context $context,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Search on product corresponding website-id to determine store. If product has websites with different stores
     * cause of their website in different stores, then asc sort will determine the first match to load corresponding
     * store-id
     * @return bool
     */
    public function updateStoreIdsByPrintformerProductStoreRelation()
    {
        $result = false;
        $connection = $this->getConnection();
        $connection->beginTransaction();

        try {
            $select = $connection->select()
                ->from(['cpp' => UpgradeSchema::TABLE_NAME_CATALOG_PRODUCT_PRINTFORMER_PRODUCT], ['id', 'store_id'])
                ->joinLeft([
                               'cpw' => $this::CATALOG_PRODUCT_WEBSITE_TABLE_NAME
                           ], 'cpp.product_id = cpw.product_id', ['cpw.website_id','cpw.product_id'])
                ->joinLeft([
                               'st' => $this::STORE_TABLE_NAME
                            ], 'cpw.website_id = st.website_id', ['st.store_id'])
                ->columns([
                              'cpp_id' => 'cpp.id',
                              'cpp_store_id' => 'cpp.store_id',
                              'cpp_product_id' => 'cpp.product_id',
                              'cpp_printformer_product_id' => 'cpp.printformer_product_id',
                              'cpw_website_id' => 'cpw.website_id',
                              'cpw_product_id' => 'cpw.product_id',
                              'st_store_id' => 'st.store_id'
                          ])
                ->where('cpp.store_id = 0')
                ->order([
                            'cpp.printformer_product_id ASC',
                            'cpw.website_id ASC',
                            'st.store_id ASC'
            ]);

            $results = $connection->fetchAll($select);

            $updateData = [];
            $firstTimeProcessedIds = [];
            foreach ($results as $result) {
                if (!in_array($result['cpp_id'], $firstTimeProcessedIds)) {
                    $updateData[] = [
                        'cpp_id' => $result['cpp_id'],
                        'st_store_id' => $result['st_store_id']
                    ];
                    $firstTimeProcessedIds[] = $result['cpp_id'];
                }
            }

            $updateStmt = $connection->prepare(
                'UPDATE '
                . UpgradeSchema::TABLE_NAME_CATALOG_PRODUCT_PRINTFORMER_PRODUCT
                . ' SET store_id = :store_id WHERE id = :id'
            );

            foreach ($updateData as $data) {
                $updateStmt->execute(['store_id' => $data['st_store_id'], 'id' => $data['cpp_id']]);
            }

            $connection->commit();
            $result = true;
        } catch (Exception $e) {
            $connection->rollBack();

            if (isset($value)) {
                $errorMessage = sprintf(
                    'The identifier update process could not be completed for store with id: %s',
                    $value
                );
                $this->logger->critical($errorMessage, ['exception' => $e]);
            } else {
                $this->logger->critical($e);
            }
        }

        return $result;
    }

    private function getConnection()
    {
        return $this->resourceConnection->getConnection();
    }
}
