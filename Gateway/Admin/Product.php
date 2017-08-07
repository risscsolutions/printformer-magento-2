<?php
namespace Rissc\Printformer\Gateway\Admin;

use Rissc\Printformer\Gateway\Exception;
use Magento\Store\Model\Store;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Rissc\Printformer\Model\Product as PrintformerProduct;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Rissc\Printformer\Gateway\User\Draft as PfIntentNameHelper;

class Product
{

    const PF_ATTRIBUTE_ENABLED = 'printformer_enabled';
    const PF_ATTRIBUTE_PRODUCT = 'printformer_product';
    const PF_ATTRIBUTE_UPLOAD_ENABLED = 'printformer_upload_enabled';
    const PF_ATTRIBUTE_UPLOAD_PRODUCT = 'printformer_upload_product';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\HTTP\ZendClientFactor
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

    /** @var AttributeRepositoryInterface  */
    protected $_eavConfig;

    protected $_attributePfEnabled;
    protected $_attributePfProduct;
    protected $_attributePfUploadEnabled;
    protected $_attributePfUploadProduct;

    protected $_updatedRows = ['sku', 'name', 'description', 'short_description', 'status'];

    protected $_productFactory;


    protected $_pfIntentNameHelper;

    /**
     * Product constructor.
     *
     * @param \Psr\Log\LoggerInterface                   $logger
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\HTTP\ZendClientFactory  $httpClientFactory
     * @param \Magento\Framework\Json\Decoder            $jsonDecoder
     * @param \Rissc\Printformer\Helper\Url              $urlHelper
     * @param \Rissc\Printformer\Model\ProductFactory    $printformerProductFactory
     * @param \Magento\Framework\Event\ManagerInterface  $_eventManager
     * @param \Magento\Catalog\Model\ProductFactory      $productFactory
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        PfIntentNameHelper $pfIntentNameHelper,
        AttributeRepositoryInterface $eavConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        \Magento\Framework\Json\Decoder $jsonDecoder,
        \Rissc\Printformer\Helper\Url $urlHelper,
        \Rissc\Printformer\Model\ProductFactory $printformerProductFactory,
        EventManager $_eventManager,
        ProductFactory $productFactory
    ) {
        $this->logger = $logger;
        $this->httpClientFactory = $httpClientFactory;
        $this->storeManager = $storeManager;
        $this->jsonDecoder = $jsonDecoder;
        $this->urlHelper = $urlHelper;
        $this->printformerProductFactory = $printformerProductFactory;
        $this->_eventManager = $_eventManager;
        $this->_productFactory = $productFactory;
        $this->_eavConfig = $eavConfig;
        $this->_pfIntentNameHelper = $pfIntentNameHelper;

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
        $url = $this->urlHelper->setStoreId($storeId)->getAdminProductsUrl();

        $this->logger->debug($url);

        /** @var \Zend_Http_Response $response */
        $response = $this->httpClientFactory
            ->create()
            ->setUri((string)$url)
            ->setConfig(['timeout' => 30])
            ->request(\Zend_Http_Client::POST);

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
            if (!in_array($masterID, $existingPrintformerProductMasterIDs)) {
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
            } else {
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


        //get ID of attribute printformer_capabilities
        $attributeCapabilities = $this->_eavConfig->get(\Magento\Catalog\Model\Product::ENTITY, 'printformer_capabilities');
        $attributeCapabilitiesID = $attributeCapabilities->getAttributeId();

        //get array with intent name and the intent numbers
        $options = $attributeCapabilities->getOptions();
        foreach ($options as $option) {
            if (!empty($option->getLabel()) && !empty($option->getValue())) {
                $intentsValueArray[$option->getValue()] = $this->_pfIntentNameHelper->getIntent($option->getLabel());
            }
        }

        //fetch all products which have a printformer product assigned to
        $query = "SELECT `entity_id`, `value` FROM `" . $this->_connection->getTableName('catalog_product_entity_text') . "` WHERE `attribute_id` = $attributeCapabilitiesID";
        $results = $this->_connection->query($query);
        foreach($results as $result) {
            //product which has a printformer product assigned to
            $productID = $result['entity_id'];

            //get the master id of the printformer product that ist assigned to the product
            $product = $this->_productFactory->create();
            $product->getResource()->load($product, $productID);
            $pfProductMasterID = $product->getPrintformerProduct();

            $changed = false;
            $currentProductIntents = explode(",", $result['value']);
            $newProductIntents = array();

            //check if there are any intents assigned to the product
            if(!empty($result['value'])) {
                //check for each intent if it is still applied to the printformer product
                foreach ($currentProductIntents as $intentID) {
                    if (in_array($intentsValueArray[$intentID], $responseRealigned[$pfProductMasterID]['intents'])) {
                        //if intent is valid put it in the array with the new intents
                        array_push($newProductIntents, $intentID);
                    } else {
                        $changed = true;
                    }
                }
            } else {
                $changed = true;
            }

            //update database with new capabilities if there was a change
            if($changed) {
                $value = implode(",",$newProductIntents);
                $query = "UPDATE `" . $this->_connection->getTableName('catalog_product_entity_text') . "` SET `value` = '$value' WHERE `attribute_id` = $attributeCapabilitiesID AND `entity_id` = $productID".";";
                $this->_connection->query($query);
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
