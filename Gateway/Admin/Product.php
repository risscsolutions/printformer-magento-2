<?php
namespace Rissc\Printformer\Gateway\Admin;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\AlreadyExistsException;
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
use Zend_Db_Statement_Exception;

class Product
{
    const PF_ATTRIBUTE_ENABLED = 'printformer_enabled';
    const PF_ATTRIBUTE_PRODUCT = 'printformer_product';
    const PF_ATTRIBUTE_UPLOAD_ENABLED = 'printformer_upload_enabled';
    const PF_ATTRIBUTE_UPLOAD_PRODUCT = 'printformer_upload_product';

    const IDENTIFIER_PATTERN = '/^[A-Za-z0-9_\-.]{1,64}$/';

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
     * @var AdapterInterface
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
     * @throws Zend_Db_Statement_Exception
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
     * Get Products from Printformer via api-call
     * @param $storeId
     * @param $websiteId
     * @return array
     */
    public function getProductsFromPrintformerApi($storeId = false, $websiteId = false)
    {
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
        if (empty($responseArray['data'])) {
            throw new Exception(__('Empty products data.'));
        }

        return $responseArray;
    }

    /**
     * Sync Products with api-call into related pf-tables
     * @param $storeId
     * @param $websiteId
     * @return $this
     */
    public function syncProducts($storeId = false, $websiteId = false)
    {
        $responseArray = $this->getProductsFromPrintformerApi($storeId, $websiteId);

        $errors = [];
        try {
            $this->_syncProducts($storeId, $responseArray['data']);
        } catch (\Exception $e) {
            $errors[] = 'Store #' . $storeId . ': ' . $e->getMessage();
            $errors[] = 'Store #' . $storeId . ': ' . $e->getMessage();
        }

        return $this;
    }

    /**
     * @param $storeId
     * @param $responseArray
     * @return $this
     * @throws AlreadyExistsException
     */
    protected function _syncProducts($storeId, $responseArray)
    {
        $identifiers = [];
        $responseRealigned = [];
        foreach ($responseArray as $responseData) {
            if (!$this->isValidProductData($responseData)) {
                $this->logger->warning('Skipping malformed printformer product in API response.');
                continue;
            }
            $identifier = $responseData['identifier'];
            if (!in_array($identifier, $identifiers, true)) {
                $identifiers[] = $identifier;
                $responseRealigned[$identifier] = $responseData;
            }
        }

        if (empty($identifiers)) {
            throw new Exception(__('No valid products found in API response.'));
        }
        $storeId = (int)$storeId;

        $this->deleteDeletedPrintformerProductRelations($identifiers, $storeId);

        $updatIdentifiers = [];
        foreach ($identifiers as $identifier) {
            foreach ($responseRealigned[$identifier]['intents'] as $intent) {
                $dateUpdate = $this->convertDate($responseRealigned[$identifier]['updatedAt']);
                $resultProduct = $this->connection->fetchRow(
                    'SELECT * FROM `' . $this->connection->getTableName('printformer_product') . '`
                    WHERE `store_id` = :store_id
                        AND `identifier` = :identifier
                        AND `intent` = :intent',
                    [
                        ':store_id' => (int)$storeId,
                        ':identifier' => (string)$identifier,
                        ':intent' => (string)$intent,
                    ]
                );

                if (!$resultProduct) {
                    /** @var PrintformerProduct $pfProduct */
                    $pfProduct = $this->addPrintformerProduct($responseRealigned[$identifier], $intent, $storeId);
                    $pfProduct->getResource()->save($pfProduct);
                } else {
                    if($resultProduct['updated_at'] < $dateUpdate) {
                        /** @var PrintformerProduct $pfProduct */
                        $pfProduct = $this->printformerProductFactory->create();
                        $pfProduct->getResource()->load($pfProduct, $resultProduct['id']);

                        $pfProduct = $this->updatePrintformerProduct(
                            $pfProduct,
                            $responseRealigned[$identifier],
                            $intent,
                            $storeId
                        );

                        $pfProduct->getResource()->save($pfProduct);
                        $updatIdentifiers[$pfProduct->getId()] = [
                            'id' => $pfProduct->getIdentifier(),
                            'intent' => $intent
                        ];
                    }
                }
            }
        }

        if (!empty($updatIdentifiers)) {
            $this->updateProductRelations($updatIdentifiers, (int)$storeId);
        }

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
     * @param array  $newIdentifiers
     * @param int    $storeId
     */
    protected function deleteDeletedPrintformerProductRelations(array $newIdentifiers, $storeId)
    {
        if (empty($newIdentifiers)) {
            // Never build an empty NOT IN () and never wipe all relations on an empty list.
            return;
        }

        $newIdentifiers = array_map('strval', array_values($newIdentifiers));

        foreach (['catalog_product_printformer_product', 'printformer_product'] as $table) {
            $tableName = $this->connection->getTableName($table);
            $this->connection->delete(
                $tableName,
                [
                    'identifier NOT IN (?)' => $newIdentifiers,
                    'store_id = ?' => (int)$storeId,
                ]
            );
        }
    }

    /**
     * Validate a single product entry of the API response.
     *
     * @param mixed $data
     * @return bool
     */
    protected function isValidProductData($data): bool
    {
        if (!is_array($data)) {
            return false;
        }
        if (!isset($data['identifier']) || !is_string($data['identifier'])
            || !preg_match(self::IDENTIFIER_PATTERN, $data['identifier'])) {
            return false;
        }

        return true;
    }

    /**
     * @param array $identifiers
     * @param int   $storeId
     *
     * @return bool
     */
    protected function updateProductRelations(array $identifiers, $storeId)
    {
        $rowsToUpdate = count($identifiers);
        $tableName = $this->connection->getTableName(
            'catalog_product_printformer_product'
        );

        foreach ($identifiers as $pfProductId => $identifier) {
            if (
                !isset($identifier['id'], $identifier['intent'])
                || !is_string($identifier['id'])
                || !preg_match(self::IDENTIFIER_PATTERN, $identifier['id'])
            ) {
                continue;
            }

            $resultRows = $this->connection->fetchAll(
                'SELECT `id` FROM `' . $tableName . '`
                WHERE `identifier` = :identifier
                AND `store_id` = :store_id
                AND `intent` = :intent',
                [
                    'identifier' => $identifier['id'],
                    'store_id' => (int)$storeId,
                    'intent' => $identifier['intent'],
                ]
            );

            foreach ($resultRows as $row) {
                $this->connection->query(
                    'UPDATE `' . $tableName . '`
                 SET `printformer_product_id` = :product_id
                 WHERE `id` = :relation_id',
                    [
                        'product_id' => (int)$pfProductId,
                        'relation_id' => (int)$row['id'],
                    ]
                );
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
            ->setIdentifier($data['identifier'])
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
     * @throws AlreadyExistsException
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