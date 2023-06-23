<?php
namespace Rissc\Printformer\Gateway\Admin;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Json\Decoder;
use Magento\Framework\Stdlib\DateTime;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;
use Magento\Store\Model\WebsiteRepository;
use Rissc\Printformer\Gateway\Exception;
use Rissc\Printformer\Helper\Api\Url;
use Rissc\Printformer\Helper\Config;
use Rissc\Printformer\Model\Product as PrintformerProduct;
use Rissc\Printformer\Model\ProductFactory as PrintformerProductFactory;
use Rissc\Printformer\Helper\Log;

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
     * @var Log
     */
    private $logHelper;

    private ClientFactory $clientFactory;

    /**
     * Product constructor.
     * @param WebsiteRepository $websiteRepository
     * @param Decoder $jsonDecoder
     * @param Url $urlHelper
     * @param PrintformerProductFactory $printformerProductFactory
     * @param ProductFactory $productFactory
     * @param Config $configHelper
     * @param Log $logHelper
     * @param ClientFactory $clientFactory
     * @throws \Zend_Db_Statement_Exception
     */
    public function __construct(
        WebsiteRepository $websiteRepository,
        Decoder $jsonDecoder,
        Url $urlHelper,
        PrintformerProductFactory $printformerProductFactory,
        ProductFactory $productFactory,
        Config $configHelper,
        Log $logHelper,
        ClientFactory $clientFactory
    ) {
        $this->_websiteRepository = $websiteRepository;
        $this->jsonDecoder = $jsonDecoder;
        $this->urlHelper = $urlHelper;
        $this->printformerProductFactory = $printformerProductFactory;
        $this->productFactory = $productFactory;
        $this->configHelper = $configHelper;
        $this->logHelper = $logHelper;
        $this->clientFactory = $clientFactory;

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
     */
    public function syncProducts($storeId = false, $websiteId = false)
    {
        $storeIds = [];
        if ($storeId == Store::DEFAULT_STORE_ID) {
            $defaultApiSecret = $this->configHelper->getClientApiKey(1,1);
            $defaultRemoteHost = $this->urlHelper->getAdminProducts(1,1);
            /** @var Website $website */
            foreach ($this->_websiteRepository->getList() as $website) {
                /** @var Store $store */
                $store = $website->getDefaultStore();
                $apiSecret = $this->configHelper->getClientApiKey();
                $remoteHost = $this->urlHelper->getAdminProducts();

                if ($apiSecret === $defaultApiSecret && $remoteHost == $defaultRemoteHost && isset($apiSecret, $remoteHost)) {
                    $storeIds[] = $store->getId();
                }
            }
        } else {
            $storeIds[] = $storeId;
        }
        $errors = [];

        $url = $this->urlHelper->getAdminProducts($storeId, $websiteId);
        $apiKey = $this->configHelper->getClientApiKey($storeId, $websiteId);

        if (empty($apiKey)) {
            throw new Exception(__('There are no available credentials for this website. Please check your settings in admin section.'));
        }

        $request = $this->clientFactory->create(
            [
                'config' => [
                    'base_url' => $url,
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer ' . $apiKey
                    ],
                ],
            ],
        );

        $createdEntry = $this->logHelper->createGetEntry($url);
        $response = $request->get($url);
        $this->logHelper->updateEntry($createdEntry, ['response_data' => $response->getBody()->getContents()]);

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

        foreach ($storeIds as $storeId) {
            try {
                $this->_syncProducts($storeId, $responseArray['data']);
            } catch (\Exception $e) {
                $errors[] = 'Store #' . $storeId . ': ' . $e->getMessage();
                continue;
            }
        }

        return $this;
    }

    /**
     * @param $storeId
     * @param $responseArray
     * @return $this
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    protected function _syncProducts($storeId, $responseArray)
    {

        $masterIDs = [];
        $responseRealigned = [];
        foreach ($responseArray as $responseData) {
            $masterID = (isset($responseData['id']) ? $responseData['id'] :
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
                $dateUpdate = $this->convertDate($responseRealigned[$masterID]['updatedAt']);
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
                    if($resultProduct['updated_at'] < $dateUpdate) {
                        /** @var PrintformerProduct $pfProduct */
                        $pfProduct = $this->printformerProductFactory->create();
                        $pfProduct->getResource()->load($pfProduct, $resultProduct['id']);

                        $pfProduct = $this->updatePrintformerProduct($pfProduct, $responseRealigned[$masterID], $intent, $storeId);

                        $pfProduct->getResource()->save($pfProduct);
                        $updateMasterIds[$pfProduct->getId()] = ['id' => $pfProduct->getMasterId(), 'intent' => $intent];
                    }
                }
            }
        }

        $this->_updateProductRelations($updateMasterIds, (int)$storeId);

        return $this;
    }


    /**
     * Convert date
     *
     * @param $date
     * @return string|null
     */
    public function convertDate($date)
    {
        if ($date) {
            $convertDate = (new \DateTime())->setTimestamp(strtotime($date));

            return $convertDate->format(DateTime::DATETIME_PHP_FORMAT);
        }
        return null;
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
    public function addPrintformerProduct(array $data, string $intent, int $storeId = 0)
    {
        /** @var PrintformerProduct $pfProduct */
        $pfProduct = $this->printformerProductFactory->create();
        $pfProduct->setSku(null)
            ->setName($data['name'])
            ->setDescription(null)
            ->setShortDescription(null)
            ->setStatus(1)
            ->setMasterId($data['id'])
            ->setMd5(null)
            ->setIntent($intent)
            ->setCreatedAt(time())
            ->setUpdatedAt($data['updatedAt'] ? $data['updatedAt'] : null)
            ->setStoreId($storeId);

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
        $pfProduct->setSku(null)
            ->setName($data['name'])
            ->setDescription(null)
            ->setShortDescription(null)
            ->setStatus(1)
            ->setIntent($intent)
            ->setUpdatedAt($data['updatedAt'] ? $data['updatedAt'] : null);

        return $pfProduct;
    }
}
