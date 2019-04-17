<?php
namespace Rissc\Printformer\Gateway\Admin;

use Magento\Framework\Json\Decoder;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;
use Magento\Store\Model\WebsiteRepository;
use Rissc\Printformer\Gateway\Exception;
use Rissc\Printformer\Helper\Api\Url;
use Rissc\Printformer\Helper\Config;
use Rissc\Printformer\Model\Product as PrintformerProduct;
use Rissc\Printformer\Model\ProductFactory as PrintformerProductFactory;
use Magento\Catalog\Model\ProductFactory;
use GuzzleHttp\Client as HttpClient;

class Product
{
    const PF_ATTRIBUTE_ENABLED = 'printformer_enabled';
    const PF_ATTRIBUTE_PRODUCT = 'printformer_product';
    const PF_ATTRIBUTE_UPLOAD_ENABLED = 'printformer_upload_enabled';
    const PF_ATTRIBUTE_UPLOAD_PRODUCT = 'printformer_upload_product';

    /**
     * @var WebsiteRepository
     */
    protected $_websiteRepository;

    /**
     * @var Decoder
     */
    protected $jsonDecoder;

    /**
     * @var Url
     */
    protected $urlHelper;

    /**
     * @var PrintformerProductFactory
     */
    protected $printformerProductFactory;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    protected $attributePfEnabled;
    protected $attributePfProduct;
    protected $attributePfUploadEnabled;
    protected $attributePfUploadProduct;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * Product constructor.
     * @param WebsiteRepository $websiteRepository
     * @param Decoder $jsonDecoder
     * @param Url $urlHelper
     * @param PrintformerProductFactory $printformerProductFactory
     * @param ProductFactory $productFactory
     * @param Config $configHelper
     * @throws \Zend_Db_Statement_Exception
     */
    public function __construct(
        WebsiteRepository $websiteRepository,
        Decoder $jsonDecoder,
        Url $urlHelper,
        PrintformerProductFactory $printformerProductFactory,
        ProductFactory $productFactory,
        Config $configHelper
    ) {
        $this->_websiteRepository = $websiteRepository;
        $this->jsonDecoder = $jsonDecoder;
        $this->urlHelper = $urlHelper;
        $this->printformerProductFactory = $printformerProductFactory;
        $this->productFactory = $productFactory;
        $this->configHelper = $configHelper;

        $printformerProduct = $this->printformerProductFactory->create();
        $this->connection = $printformerProduct->getResource()->getConnection();

        $attributesArray = [
            self::PF_ATTRIBUTE_ENABLED,
            self::PF_ATTRIBUTE_PRODUCT,
            self::PF_ATTRIBUTE_UPLOAD_ENABLED,
            self::PF_ATTRIBUTE_UPLOAD_PRODUCT
        ];

        $result = $this->connection->query("
            SELECT `attribute_id`, `attribute_code` FROM `eav_attribute` WHERE `attribute_code` IN ('" . implode("', '", $attributesArray) . "')
        ");

        while ($row = $result->fetch()) {
            switch ($row['attribute_code']) {
                case self::PF_ATTRIBUTE_ENABLED:
                    $this->attributePfEnabled = $row['attribute_id'];
                    break;
                case self::PF_ATTRIBUTE_PRODUCT:
                    $this->attributePfProduct = $row['attribute_id'];
                    break;
                case self::PF_ATTRIBUTE_UPLOAD_ENABLED:
                    $this->attributePfUploadEnabled = $row['attribute_id'];
                    break;
                case self::PF_ATTRIBUTE_UPLOAD_PRODUCT:
                    $this->attributePfUploadProduct = $row['attribute_id'];
                    break;
            }
        }
    }

    /**
     * @param int $storeId
     * @return $this
     * @throws Exception
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @todo: Move to Api Helper!!!
     */
    public function syncProducts($storeId = Store::DEFAULT_STORE_ID)
    {
        $storeIds = [];
        if ($storeId == Store::DEFAULT_STORE_ID) {
            $defaultApiSecret = $this->configHelper->setStoreId(Store::DEFAULT_STORE_ID)->getClientApiKey();
            /** @var Website $website */
            foreach ($this->_websiteRepository->getList() as $website) {
                /** @var Store $store */
                $store = $website->getDefaultStore();
                $apiSecret = $this->configHelper->setStoreId($store->getId())->getClientApiKey();

                if ($defaultApiSecret === $apiSecret) {
                    $storeIds[] = $store->getId();
                }
            }
        } else {
            $storeIds[] = $storeId;
        }
        foreach ($storeIds as $storeId) {
            $this->_syncProducts($storeId);
        }

        return $this;
    }

    /**
     * @param int $storeId
     * @return $this
     * @throws Exception
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _syncProducts($storeId = Store::DEFAULT_STORE_ID)
    {
        $url = $this->urlHelper->setStoreId($storeId)->getAdminProducts();
        $apiKey = $this->configHelper->getClientApiKey($storeId);

        if ($this->configHelper->isV2Enabled($storeId) && !empty($apiKey)) {
            $request = new HttpClient([
                'base_url' => $url,
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey
                ]
            ]);
        } else {
            $request = new HttpClient([
                'base_url' => $url,
            ]);
        }

        $response = $request->get($url);

        if ($response->getStatusCode() !== 200) {
            throw new Exception(__('Error fetching products.'));
        }

        $responseArray = $this->jsonDecoder->decode($response->getBody());

        if (!is_array($responseArray)) {
            throw new Exception(__('Error decoding products.'));
        }
        if (isset($responseArray['success']) && false == $responseArray['success']) {
            $errorMsg = 'Request was not successful.';
            if (isset($responseArray['error'])) {
                $errorMsg = $responseArray['error'];
            }
            throw new Exception(__($errorMsg));
        }
        if (empty($responseArray['data'])) {
            throw new Exception(__('Empty products data.'));
        }

        $masterIDs = [];
        $responseRealigned = [];
        foreach ($responseArray['data'] as $responseData) {
            $masterID = ($this->configHelper->isV2Enabled($storeId) && isset($responseData['id']) ? $responseData['id'] :
                $responseData['rissc_w2p_master_id']);
            if (!in_array($masterID, $masterIDs)) {
                $masterIDs[] = $masterID;
                $responseRealigned[$masterID] = $responseData;
            }
        }

        $this->_deleteDeletedPrintformerProductReleations($masterIDs, $storeId);

        $updateMasterIds = [];
        foreach ($masterIDs as $masterID) {
            foreach ($responseRealigned[$masterID]['intents'] as $intent) {
                $resultProduct = $this->connection->fetchRow('
                    SELECT * FROM
                        `' . $this->connection->getTableName('printformer_product') . '`
                    WHERE
                        `store_id` = ' . $storeId . ' AND
                        `master_id` = ' . $masterID . ' AND
                        `intent` = \'' . $intent . '\';
                ');

                if (!$resultProduct) {
                    /** @var PrintformerProduct $pfProduct */
                    $pfProduct = $this->addPrintformerProduct($responseRealigned[$masterID], $intent, $storeId);
                    $pfProduct->getResource()->save($pfProduct);
                } else {
                    /** @var PrintformerProduct $pfProduct */
                    $pfProduct = $this->printformerProductFactory->create();
                    $pfProduct->getResource()->load($pfProduct, $resultProduct['id']);

                    $pfProduct = $this->updatePrintformerProduct($pfProduct, $responseRealigned[$masterID], $intent, $storeId);

                    $pfProduct->getResource()->save($pfProduct);
                    $updateMasterIds[$pfProduct->getId()] = ['id' => $pfProduct->getMasterId(), 'intent' => $intent];
                }
            }
        }

        $this->_updateProductRelations($updateMasterIds, (int)$storeId);

        return $this;
    }

    /**
     * @param array  $newMasterIds
     * @param int    $storeId
     */
    protected function _deleteDeletedPrintformerProductReleations(array $newMasterIds, $storeId)
    {
        $tableName = $this->connection->getTableName('catalog_product_printformer_product');
        $sqlQuery = '
            SELECT * FROM
                `' . $tableName . '`
            WHERE
                `master_id` NOT IN (\'' . implode('\',\'', $newMasterIds) . '\') AND
                `store_id` = ' . $storeId . ';
        ';
        $resultRows = $this->connection->fetchAll($sqlQuery);

        if (!empty($resultRows)) {
            foreach ($resultRows as $row) {
                $this->connection->delete($tableName, ['id = ?' => $row['id']]);
            }
        }

        $tableName = $this->connection->getTableName('printformer_product');
        $sqlQuery = '
            SELECT * FROM
                `' . $tableName . '`
            WHERE
                `master_id` NOT IN (\'' . implode('\',\'', $newMasterIds) . '\') AND
                `store_id` = ' . $storeId . ';
        ';
        $resultRows = $this->connection->fetchAll($sqlQuery);
        if (!empty($resultRows)) {
            foreach ($resultRows as $row) {
                $this->connection->delete($tableName, ['id = ?' => $row['id']]);
            }
        }
    }

    /**
     * @param array $masterIds
     * @param int   $storeId
     *
     * @return bool
     */
    protected function _updateProductRelations(array $masterIds, $storeId)
    {
        $rowsToUpdate = count($masterIds);
        $tableName = $this->connection->getTableName('catalog_product_printformer_product');
        foreach ($masterIds as $pfProductId => $masterId) {
            $resultRows = $this->connection->fetchAll('
                SELECT * FROM
                    `' . $tableName . '`
                WHERE
                    `master_id` = ' . $masterId['id'] . ' AND
                    `store_id` = ' . $storeId . ' AND
                    `intent` = \'' . $masterId['intent'] . '\';
            ');

            foreach ($resultRows as $row) {
                $this->connection->query('
                    UPDATE `' . $tableName . '`
                    SET
                        `printformer_product_id` = ' . $pfProductId . '
                    WHERE
                        `id` = ' . $row['id'] . ';
                ');
            }

            $rowsToUpdate--;
        }

        return $rowsToUpdate == 0;
    }

    /**
     * @param array $data
     * @param string $intent
     * @param int $storeId
     *
     * @return PrintformerProduct
     */
    public function addPrintformerProduct(array $data, string $intent, int $storeId)
    {
        /** @var PrintformerProduct $pfProduct */
        $pfProduct = $this->printformerProductFactory->create();
        $pfProduct->setStoreId($storeId)
            ->setSku($this->configHelper->isV2Enabled($storeId) ? null : $data['sku'])
            ->setName($data['name'])
            ->setDescription($this->configHelper->isV2Enabled($storeId) ? null : $data['description'])
            ->setShortDescription($this->configHelper->isV2Enabled($storeId) ? null : $data['short_description'])
            ->setStatus($this->configHelper->isV2Enabled($storeId) ? 1 : $data['status'])
            ->setMasterId($this->configHelper->isV2Enabled($storeId) ? $data['id'] : $data['rissc_w2p_master_id'])
            ->setMd5($this->configHelper->isV2Enabled($storeId) ? null : $data['rissc_w2p_md5'])
            ->setIntent($intent)
            ->setCreatedAt(time())
            ->setUpdatedAt(time());

        return $pfProduct;
    }

    /**
     * @param PrintformerProduct $pfProduct
     * @param array $data
     * @param string $intent
     * @param int $storeId
     *
     * @return PrintformerProduct
     *
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function updatePrintformerProduct(PrintformerProduct $pfProduct, array $data, string $intent, int $storeId)
    {
        $pfProduct->setSku($this->configHelper->isV2Enabled($storeId) ? null : $data['sku'])
            ->setName($data['name'])
            ->setDescription($this->configHelper->isV2Enabled($storeId) ? null : $data['description'])
            ->setShortDescription($this->configHelper->isV2Enabled($storeId) ? null : $data['short_description'])
            ->setStatus($this->configHelper->isV2Enabled($storeId) ? 1 : $data['status'])
            ->setIntent($intent)
            ->setUpdatedAt(time());

        return $pfProduct;
    }
}