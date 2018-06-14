<?php
namespace Rissc\Printformer\Gateway\Admin;

use Magento\Framework\Json\Decoder;
use Magento\Store\Model\StoreManagerInterface;
use Rissc\Printformer\Gateway\Exception;
use Magento\Store\Model\Store;
use Rissc\Printformer\Helper\Api\Url;
use Rissc\Printformer\Helper\Config;
use Rissc\Printformer\Model\Product as PrintformerProduct;
use Rissc\Printformer\Model\ProductFactory as PrintformerProductFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product as CatalogProduct;
use GuzzleHttp\Client as HttpClient;

class Product
{
    const PF_ATTRIBUTE_ENABLED = 'printformer_enabled';
    const PF_ATTRIBUTE_PRODUCT = 'printformer_product';
    const PF_ATTRIBUTE_UPLOAD_ENABLED = 'printformer_upload_enabled';
    const PF_ATTRIBUTE_UPLOAD_PRODUCT = 'printformer_upload_product';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

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
     * @param StoreManagerInterface $storeManager
     * @param Decoder $jsonDecoder
     * @param Url $urlHelper
     * @param PrintformerProductFactory $printformerProductFactory
     * @param ProductFactory $productFactory
     * @param Config $configHelper
     * @throws \Zend_Db_Statement_Exception
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Decoder $jsonDecoder,
        Url $urlHelper,
        PrintformerProductFactory $printformerProductFactory,
        ProductFactory $productFactory,
        Config $configHelper
    ) {
        $this->storeManager = $storeManager;
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

        while($row = $result->fetch()) {
            switch($row['attribute_code']) {
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
    public function syncProducts($storeId = Store::DEFAULT_STORE_ID)
    {
        $storeIds = [];
        if ($storeId == Store::DEFAULT_STORE_ID) {
            foreach ($this->storeManager->getStores(true) as $store) {
                $storeIds[] = $store->getId();
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
        foreach($responseArray['data'] as $responseData) {
            $masterID = ($this->configHelper->isV2Enabled($storeId) && isset($responseData['id']) ? $responseData['id'] :
                $responseData['rissc_w2p_master_id']);
            if(!in_array($masterID, $masterIDs)) {
                $masterIDs[] = $masterID;
                $responseRealigned[$masterID] = $responseData;
            }
        }

        $pfProduct = $this->printformerProductFactory->create();
        $pfProductCollection = $pfProduct->getCollection()
            ->addFieldToFilter('store_id', ['eq' => $storeId])
            ->addFieldToFilter('master_id', ['in' => $masterIDs]);

        $existingPrintformerProductMasterIDs = [];
        $existingPrintformerProductsByMasterId = [];
        foreach($pfProductCollection as $pfProduct) {
            $existingPrintformerProductMasterIDs[] = $pfProduct->getMasterId();
            $existingPrintformerProductsByMasterId[$pfProduct->getMasterId()] = $pfProduct;
        }

        $updateMasterIds = [];
        foreach($masterIDs as $masterID) {
            if(!in_array($masterID, $existingPrintformerProductMasterIDs)) {
                foreach($responseRealigned[$masterID]['intents'] as $intent) {
                    /** @var PrintformerProduct $pfProduct */
                    $pfProduct = $this->printformerProductFactory->create();
                    $pfProduct->setStoreId($storeId)
                        ->setSku($this->configHelper->isV2Enabled($storeId) ? null : $responseRealigned[$masterID]['sku'])
                        ->setName($responseRealigned[$masterID]['name'])
                        ->setDescription($this->configHelper->isV2Enabled($storeId) ? null : $responseRealigned[$masterID]['description'])
                        ->setShortDescription($this->configHelper->isV2Enabled($storeId) ? null : $responseRealigned[$masterID]['short_description'])
                        ->setStatus($this->configHelper->isV2Enabled($storeId) ? 1 : $responseRealigned[$masterID]['status'])
                        ->setMasterId($this->configHelper->isV2Enabled($storeId) ? $responseRealigned[$masterID]['id'] :
                            $responseRealigned[$masterID]['rissc_w2p_master_id'])
                        ->setMd5($this->configHelper->isV2Enabled($storeId) ? null : $responseRealigned[$masterID]['rissc_w2p_md5'])
                        ->setIntent($intent)
                        ->setCreatedAt(time())
                        ->setUpdatedAt(time());
                    $pfProduct->getResource()->save($pfProduct);
                }
            } else {
                foreach($responseRealigned[$masterID]['intents'] as $intent) {
                    /** @var PrintformerProduct $pfProduct */
                    $pfProduct = $existingPrintformerProductsByMasterId[$masterID];
                    $pfProduct->setSku($this->configHelper->isV2Enabled($storeId) ? null : $responseRealigned[$masterID]['sku'])
                        ->setName($responseRealigned[$masterID]['name'])
                        ->setDescription($this->configHelper->isV2Enabled($storeId) ? null : $responseRealigned[$masterID]['description'])
                        ->setShortDescription($this->configHelper->isV2Enabled($storeId) ? null : $responseRealigned[$masterID]['short_description'])
                        ->setStatus($this->configHelper->isV2Enabled($storeId) ? 1 : $responseRealigned[$masterID]['status'])
                        ->setIntent($intent)
                        ->setUpdatedAt(time());

                    $pfProduct->getResource()->save($pfProduct);
                    $updateMasterIds[$pfProduct->getId()] = $pfProduct->getMasterId();
                }
            }
        }

        $this->_updateProductRelations($updateMasterIds, (int)$storeId);

        return $this;
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
        foreach($masterIds as $pfProductId => $masterId) {
            $resultRows = $this->connection->fetchAll('
                SELECT * FROM
                    `' . $tableName . '`
                WHERE
                    `master_id` = ' . $masterId . '
                AND
                    `store_id` = ' . $storeId . ';
            ');

            foreach($resultRows as $row) {
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
}