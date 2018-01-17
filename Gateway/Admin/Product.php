<?php
namespace Rissc\Printformer\Gateway\Admin;

use Rissc\Printformer\Gateway\Exception;
use Magento\Store\Model\Store;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Rissc\Printformer\Helper\Log;
use Rissc\Printformer\Model\Product as PrintformerProduct;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\HTTP\ZendClient;

class Product
{

    const PF_ATTRIBUTE_ENABLED = 'printformer_enabled';
    const PF_ATTRIBUTE_PRODUCT = 'printformer_product';
    const PF_ATTRIBUTE_UPLOAD_ENABLED = 'printformer_upload_enabled';
    const PF_ATTRIBUTE_UPLOAD_PRODUCT = 'printformer_upload_product';
    const TEMPLATE_ENDPOINT = '/api-ext/template';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\HTTP\ZendClientFactory
     */
    protected $httpClientFactory;

    /**
     * @var \Magento\Framework\Json\Decoder
     */
    protected $jsonDecoder;

    /**
     * @var \Rissc\Printformer\Helper\Url
     */
    protected $urlHelper;

    /**
     * @var \Rissc\Printformer\Model\ProductFactory
     */
    protected $printformerProductFactory;

    protected $_eventManager;

    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    protected $_connection;

    protected $_attributePfEnabled;
    protected $_attributePfProduct;
    protected $_attributePfUploadEnabled;
    protected $_attributePfUploadProduct;

    protected $_updatedRows = ['sku', 'name', 'description', 'short_description', 'status'];

    protected $_productFactory;

    /** @var ScopeConfigInterface */
    protected $_scopeConfig;

    /**
     * Product constructor.
     *
     * @param \Psr\Log\LoggerInterface                   $logger
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\HTTP\ZendClientFactory  $httpClientFactory
     * @param \Magento\Framework\Json\Decoder            $jsonDecoder
     * @param \Rissc\Printformer\Helper\Url              $urlHelper
     * @param \Rissc\Printformer\Model\ProductFactory    $printformerProductFactory
     * @param EventManager                               $_eventManager
     * @param ProductFactory                             $productFactory
     * @param ScopeConfigInterface                       $scopeConfig
     *
     * @throws \Zend_Db_Statement_Exception
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        \Magento\Framework\Json\Decoder $jsonDecoder,
        \Rissc\Printformer\Helper\Url $urlHelper,
        \Rissc\Printformer\Model\ProductFactory $printformerProductFactory,
        EventManager $_eventManager,
        ProductFactory $productFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->httpClientFactory = $httpClientFactory;
        $this->storeManager = $storeManager;
        $this->jsonDecoder = $jsonDecoder;
        $this->urlHelper = $urlHelper;
        $this->printformerProductFactory = $printformerProductFactory;
        $this->_eventManager = $_eventManager;
        $this->_productFactory = $productFactory;
        $this->_scopeConfig = $scopeConfig;

        $printformerProduct = $this->printformerProductFactory->create();
        $this->_connection = $printformerProduct->getResource()->getConnection();

        $_attributesArray = [
            self::PF_ATTRIBUTE_ENABLED,
            self::PF_ATTRIBUTE_PRODUCT,
            self::PF_ATTRIBUTE_UPLOAD_ENABLED,
            self::PF_ATTRIBUTE_UPLOAD_PRODUCT
        ];

        $result = $this->_connection->query("
            SELECT `attribute_id`, `attribute_code` FROM `eav_attribute` WHERE `attribute_code` IN ('" . implode("', '", $_attributesArray) . "')
        ");

        while($row = $result->fetch())
        {
            switch($row['attribute_code'])
            {
                case self::PF_ATTRIBUTE_ENABLED:
                    $this->_attributePfEnabled = $row['attribute_id'];
                break;
                case self::PF_ATTRIBUTE_PRODUCT:
                    $this->_attributePfProduct = $row['attribute_id'];
                break;
                case self::PF_ATTRIBUTE_UPLOAD_ENABLED:
                    $this->_attributePfUploadEnabled = $row['attribute_id'];
                break;
                case self::PF_ATTRIBUTE_UPLOAD_PRODUCT:
                    $this->_attributePfUploadProduct = $row['attribute_id'];
                break;
            }
        }

    }

    /**
     * @return bool
     */
    protected function isV2Enabled()
    {
        return
            $this->_scopeConfig->getValue(
                'printformer/version2group/version2',
                ScopeInterface::SCOPE_STORES,
                $this->storeManager->getStore()->getId()
            ) == 1;
    }

    /**
     * @return string
     */
    protected function getV2ApiKey()
    {
        return $this->_scopeConfig->getValue(
            'printformer/version2group/v2apiKey',
            ScopeInterface::SCOPE_STORES,
            $this->storeManager->getStore()->getId()
        );
    }

    /**
     * @return string
     */
    protected function getV2Endpoint()
    {
        return $this->_scopeConfig->getValue(
            'printformer/version2group/v2url',
            ScopeInterface::SCOPE_STORES,
            $this->storeManager->getStore()->getId()
        );
    }

    /**
     * @param integer $storeId
     * @throws Exception
     * @return \Rissc\Printformer\Gateway\Admin\Product
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
     * @param integer $storeId
     * @throws Exception
     * @return \Rissc\Printformer\Gateway\Admin\Product
     */
    protected function _syncProducts($storeId = Store::DEFAULT_STORE_ID)
    {
        $apiKey = null;
        if(!$this->isV2Enabled()) {
            $url = $this->urlHelper->setStoreId($storeId)->getAdminProductsUrl();
        } else {
            $url = $this->getV2Endpoint() . self::TEMPLATE_ENDPOINT;
            $apiKey = $this->getV2ApiKey();
        }

        $this->logger->debug($url);

        /** @var \Zend_Http_Response $response */
        /** @var ZendClient $request */
        $request = $this->httpClientFactory
            ->create()
            ->setUri((string)$url)
            ->setConfig(['timeout' => 30]);
        if($this->isV2Enabled() && !empty($apiKey)) {
            $request->setHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey
            ]);
        }
        $response = $request->request(\Zend_Http_Client::POST);

        if (!$response->isSuccessful()) {
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
        foreach($responseArray['data'] as $responseData)
        {
            $masterIDs[] = $responseData['rissc_w2p_master_id'];
            $responseRealigned[$responseData['rissc_w2p_master_id']] = $responseData;
        }

        /** @var PrintformerProduct $pfProduct */
        $pfProduct = $this->printformerProductFactory->create();
        $pfProductCollection = $pfProduct->getCollection()
            ->addFieldToFilter('store_id', ['eq' => $storeId])
            ->addFieldToFilter('master_id', ['in' => $masterIDs]);

        $existingPrintformerProductMasterIDs = [];
        $existingPrintformerProductsByMasterId = [];
        foreach($pfProductCollection as $pfProduct)
        {
            $existingPrintformerProductMasterIDs[] = $pfProduct->getMasterId();
            $existingPrintformerProductsByMasterId[$pfProduct->getMasterId()] = $pfProduct;
        }

        foreach($masterIDs as $masterID)
        {
            if(!in_array($masterID, $existingPrintformerProductMasterIDs))
            {
                $pfProduct = $this->printformerProductFactory->create();
                $pfProduct->setStoreId($storeId)
                    ->setSku($responseRealigned[$masterID]['sku'])
                    ->setName($responseRealigned[$masterID]['name'])
                    ->setDescription($responseRealigned[$masterID]['description'])
                    ->setShortDescription($responseRealigned[$masterID]['short_description'])
                    ->setStatus($responseRealigned[$masterID]['status'])
                    ->setMasterId($responseRealigned[$masterID]['rissc_w2p_master_id'])
                    ->setMd5($responseRealigned[$masterID]['rissc_w2p_md5'])
                    ->setIntents(implode(',', $responseRealigned[$masterID]['intents']))
                    ->setCreatedAt(time())
                    ->setUpdatedAt(time());
                $pfProduct->getResource()->save($pfProduct);
                continue;
            }
            else
            {
                $pfProduct = $existingPrintformerProductsByMasterId[$masterID];
                $pfProduct->setSku($responseRealigned[$masterID]['sku'])
                    ->setName($responseRealigned[$masterID]['name'])
                    ->setDescription($responseRealigned[$masterID]['description'])
                    ->setShortDescription($responseRealigned[$masterID]['short_description'])
                    ->setStatus($responseRealigned[$masterID]['status'])
                    ->setIntents(implode(',', $responseRealigned[$masterID]['intents']))
                    ->setUpdatedAt(time());

                $pfProduct->getResource()->save($pfProduct);
                continue;
            }
        }

        $pfProduct = $this->printformerProductFactory->create();
        $pfProductToDeleteCollection = $pfProduct->getCollection()
            ->addFieldToFilter('store_id', ['eq' => $storeId])
            ->addFieldToFilter('master_id', ['nin' => $masterIDs]);

        /** @var PrintformerProduct $pfProductToDelete */
        foreach($pfProductToDeleteCollection as $pfProductToDelete)
        {
            $catalogProduct = $this->_productFactory->create();
            /** @var CatalogProduct $catalogProductToEdit */
            $catalogProductToEdit = $catalogProduct->getCollection()
                ->setStoreId($storeId)
                ->addAttributeToFilter('printformer_product', ['eq' => $pfProductToDelete->getMasterId()])
                ->addAttributeToFilter('printformer_enabled', ['eq' => 1]);

            $catalogProductToEdit = $catalogProductToEdit->getFirstItem();
            if($catalogProductToEdit->getId())
            {
                $query = "UPDATE `" . $this->_connection->getTableName('catalog_product_entity_int') . "` SET `value` = 0 WHERE `attribute_id` = " . $this->_attributePfEnabled . " AND `value` = 1 AND store_id = " . $storeId . " AND `entity_id` = " . $catalogProductToEdit->getId() . ";";
                $this->_connection->query($query);
                $query = "UPDATE `" . $this->_connection->getTableName('catalog_product_entity_int') . "` SET `value` = 0 WHERE `attribute_id` = " . $this->_attributePfProduct . " AND `value` = " . $pfProductToDelete->getMasterId() . " AND store_id = " . $storeId . " AND `entity_id` = " . $catalogProductToEdit->getId() . ";";
                $this->_connection->query($query);
            }

            $pfProductToDelete->getResource()->delete($pfProductToDelete);
        }

        return $this;
    }
}
