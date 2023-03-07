<?php

namespace Rissc\Printformer\Helper;

use Exception;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface as ProductAttributeRepository;
use Magento\Catalog\Model\ProductRepository;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProductModel;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Rissc\Printformer\Model\Draft;
use Rissc\Printformer\Model\DraftFactory;
use Rissc\Printformer\Model\ProductFactory;
use Rissc\Printformer\Model\ResourceModel\Product as ResourceProduct;
use Rissc\Printformer\Helper\Session as SessionHelper;
use Rissc\Printformer\Setup\InstallSchema;
use Rissc\Printformer\Setup\UpgradeSchema;
use Magento\Store\Model\Store;

class Product extends AbstractHelper
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var ResourceProduct
     */
    protected $resource;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var ProductRepository
     */
    private ProductRepository $catalogProductRepository;

    /**
     * @var DraftFactory
     */
    private DraftFactory $draftFactory;

    /**
     * @var Config
     */
    private Config $configHelper;
    private Session $sessionHelper;
    private ProductAttributeRepository $productAttributeRepository;
    private ConfigurableProductModel $configurableProduct;
    const COLUMN_NAME_DRAFTID = InstallSchema::COLUMN_NAME_DRAFTID;

    /**
     * @param ProductFactory $productFactory
     * @param ResourceProduct $resource
     * @param ResourceConnection $resourceConnection
     * @param Context $context
     * @param RequestInterface $request
     * @param ProductRepository $catalogProductRepository
     * @param DraftFactory $draftFactory
     * @param Config $configHelper
     * @param Session $sessionHelper
     */
    public function __construct(
        ProductFactory $productFactory,
        ResourceProduct $resource,
        ResourceConnection $resourceConnection,
        Context $context,
        RequestInterface $request,
        ProductRepository $catalogProductRepository,
        DraftFactory $draftFactory,
        Config $configHelper,
        SessionHelper $sessionHelper,
        ProductAttributeRepository $productAttributeRepository,
        ConfigurableProductModel $configurableProduct
    ) {
        parent::__construct($context);
        $this->productFactory = $productFactory;
        $this->resource = $resource;
        $this->resourceConnection = $resourceConnection;
        $this->_request = $request;
        $this->catalogProductRepository = $catalogProductRepository;
        $this->draftFactory = $draftFactory;
        $this->configHelper = $configHelper;
        $this->sessionHelper = $sessionHelper;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->configurableProduct = $configurableProduct;
    }

    /**
     * Fetch-all on db-table *catalog_product_printformer_product  for product-id and possible child-product-ids
     *
     * @param $productId
     * @param int $storeId
     * @param bool $useDefaultStore
     * @param array $childProductIds
     * @return array
     */
    protected function getCatalogProductPrintformerProductsData($productId, int $storeId = 0, array $childProductIds = [], $defaultStoreTemplatesCanBeUsedOnFrontend = true)
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from('catalog_product_printformer_product');

        if (!empty($childProductIds)){
            array_unshift($childProductIds, $productId);
            $select->where("product_id IN (" . implode(',', $childProductIds) . ")");
        } else {
            $select->where('product_id = ?', intval($productId));
        }

        $selectDefault = clone $select;
        $select->where('store_id = ?', intval($storeId));
        $resultTemplates = $connection->fetchAll($select);

        if ($defaultStoreTemplatesCanBeUsedOnFrontend) {
            if (empty($resultTemplates)) {
                $defaultStoreCanBeUsed = $this->configHelper->defaultStoreTemplatesCanBeUsed($storeId);
                if($defaultStoreCanBeUsed) {
                    $selectDefault->where('store_id = ?', intval(STORE::DEFAULT_STORE_ID));
                    $resultTemplates = $connection->fetchAll($selectDefault);
                }
            }
        }

        return $resultTemplates;
    }

    /**
     * Get printformer-products prepared for frontend methods
     *
     * @param $productId
     * @param int $storeId
     * @param bool $subordinateSimpleProducts
     * @return array
     */
    public function getPrintformerProductsForFrontendConfigurationLogic($productId, int $storeId = 0, $childProductIds = []): array
    {
        $printformerProducts = [];

        foreach($this->getCatalogProductPrintformerProductsData($productId, $storeId, $childProductIds) as $row) {
            $printformerProduct = $this->productFactory->create();
            $this->resource->load($printformerProduct, $row['printformer_product_id']);

            if($printformerProduct->getId()) {
                $printformerProduct->setData('template_id', $row['printformer_product_id']);
                $printformerProduct->setData('product_id', $row['product_id']);
                $printformerProducts[] = $printformerProduct;
            }
        }

        return $printformerProducts;
    }

    /**
     * @param string $identifier
     * @return array
     */
    public function getCatalogProductPrintformerProductsByIdentifier($identifier)
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from('catalog_product_printformer_product')
            ->where('identifier = ?', $identifier);
        return $connection->fetchAll($select);
    }

    /**
     * @param     $productId
     * @param int $storeId
     *
     * @return array
     */
    public function getCatalogProductPrintformerProductsArray($productId, $storeId = 0)
    {
        $result = $this->getCatalogProductPrintformerProductsData($productId, $storeId, [], false);

        foreach($result as &$row) {
            $printformerProduct = $this->productFactory->create();
            $this->resource->load($printformerProduct, $row['printformer_product_id']);

            if($printformerProduct->getId()) {
                $printformerProduct->setData('template_id', $row['printformer_product_id']);
                $printformerProduct->setData('product_id', $row['product_id']);
                $id = $row['id'];
                //todo: check better version to fix array merge performance issue
                $row = array_merge($row, $printformerProduct->getData());
                $row['id'] = $id;
            }
        }

        return $result;
    }

    /**
     * Prepare results from getCatalogProductPrintformerProductsData
     *
     * @param $productId
     * @param int $storeId
     * @return array
     */
    public function prepareCatalogProductPrintformerProductsData($productId, int $storeId = 0): array
    {
        //Todo: function can maybe merged with getCatalogProductPrintformerProductsData for better performance
        $catalogProductPrintformerProducts = [];

        foreach( $this->getCatalogProductPrintformerProductsData($productId, $storeId) as $i => $row ) {
            $catalogProductPrintformerProducts[$i] = new DataObject();
            $catalogProductPrintformerProducts[$i]->setCatalogProductPrintformerProduct(new DataObject($row));

            $printformerProduct = $this->productFactory->create();
            $this->resource->load($printformerProduct, $row['printformer_product_id']);
            if($printformerProduct->getId()) {
                $printformerProduct->setData('template_id', $row['printformer_product_id']);
                $printformerProduct->setData('product_id', $row['product_id']);
                $catalogProductPrintformerProducts[$i]->setPrintformerProduct($printformerProduct);
            }
        }

        return $catalogProductPrintformerProducts;
    }

    /**
     * Get draft-id from unique id with required pf-product-id and the main-page-product-id
     *
     * @param $printformerProductId
     * @param $productId
     * @return string
     */
    public function getDraftId($printformerProductId, $productId)
    {
        $draftId = '';
        $sessionUniqueId = $this->sessionHelper->getSessionUniqueIdByProductId($productId, $printformerProductId);

        if (isset($sessionUniqueId)) {
            /** @var Draft $draft */
            $draft = $this->draftFactory->create();
            $draftCollection = $draft->getCollection()
                ->addFieldToFilter('printformer_product_id', $printformerProductId)
                ->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('session_unique_id', ['eq' => $sessionUniqueId])
                ->setOrder('created_at', AbstractDb::SORT_ORDER_ASC);

            if ($draftCollection->count() > 0) {
                $draft = $draftCollection->getLastItem();
                if ($draft->getId() && $draft->getDraftId()) {
                    $draftId = $draft->getDraftId();
                }
            }
        }

        return $draftId;
    }


    /**
     * @param integer $draftId
     *
     * @return array
     */
    public function getProductVariations($draftId = null)
    {
        $variations = [];
        if ($draftId) {
            $draft = $this->draftFactory->create()->load($draftId, 'draft_id');
            if ($draft->getFormatVariation()) {
                $variations[$this->configHelper->getFormatQueryParameter()] = $draft->getFormatVariation();
            }
            if ($draft->getColorVariation()) {
                $variations[$this->configHelper->getColorQueryParameter()] = $draft->getColorVariation();
            }
        }

        return $variations;
    }

    /**
     * @param integer $draftId
     *
     * @return array
     */
    public function getProductQty($draftId = null)
    {
        $qty = 1; //@todo min qty from sysconf?
        if ($draftId) {
            $draft = $this->draftFactory->create()//@todo add getDraft()
            ->load($draftId, 'draft_id');
            if ($draft->getQty()) {
                $qty = $draft->getQty();
            }
        }

        return $qty;
    }

    /**
     * @param string $draftIds
     * @return DataObject[]
     */
    public function loadDraftItemsByIds(string $draftIds): array
    {
        /** @var Draft $draftFactory */
        $draftFactory = $this->draftFactory->create();
        $draftCollection = $draftFactory->getCollection();
        $draftCollection->addFieldToFilter('draft_id', ['in' => $draftIds]);
        return $draftCollection->getItems();
    }

    /**
     * Get attribute code by attribute id
     *
     * @param int $id
     * @return false|string
     */
    public function getAttributeCode(int $id)
    {
        $result = false;
        try {
            $result = $this->productAttributeRepository->get($id)->getAttributeCode();
        } catch (NoSuchEntityException $e) {
        }
        return $result;
    }

    /**
     * @param $mainProduct
     * @return array
     */
    public function getConfigurableAndChildrens($mainProduct)
    {
        if ($this->configHelper->useChildProduct($mainProduct->getTypeId())) {
            $childProducts = $mainProduct->getTypeInstance()->getUsedProducts($mainProduct);
            foreach ($childProducts as $simpleProductKey => $simpleProduct) {
                $_attributes = $mainProduct->getTypeInstance(true)->getConfigurableAttributes($mainProduct);
                $attributesPair = [];
                foreach ($_attributes as $_attribute) {
                    $attributeId = (int)$_attribute->getAttributeId();
                    $attributeCode = $this->getAttributeCode($attributeId);
                    $attributesPair[$attributeId] = (int)$simpleProduct->getData($attributeCode);
                }
                $childProducts[$simpleProductKey]->setData('super_attributes', $attributesPair);
                $allProducts = $childProducts;
                array_unshift($allProducts, $mainProduct);
            }
        } else {
            $allProducts = [];
            $allProducts[] = $mainProduct;
        }

        return $allProducts;
    }

    /**
     * @param $product
     * @return array
     */
    public function getChildrens(ProductModel $product)
    {
        $resultChildProducts = [];
        if ($product->getTypeId() === ConfigurableProductModel::TYPE_CODE) {
            $resultChildProducts = $product->getTypeInstance()->getUsedProducts($product);
        }

        return $resultChildProducts;
    }

    /**
     * @param string $draftId
     * @return false
     */
    public function getPfProductIdByDraftId($draftId)
    {
        $printformerProductId = false;
        try {
            $draftProcess = $this->draftFactory->create();
            $draftCollection = $draftProcess->getCollection()
                ->addFieldToFilter('draft_id', ['eq' => $draftId]);
            $lastItem = $draftCollection->getLastItem();
            $printformerProductId = $lastItem->getPrintformerProductId();
        } catch (Exception $e) {
        }
        return $printformerProductId;
    }

    /**
     * @param string $draftId
     * @return DataObject|false
     */
    public function getDraftById($draftId)
    {
        $resultItem = false;
        try {
            $draftProcess = $this->draftFactory->create();
            $draftCollection = $draftProcess->getCollection()
                ->addFieldToFilter('draft_id', ['eq' => $draftId]);
            $resultItem = $draftCollection->getLastItem();
        } catch (Exception $e) {
        }
        return $resultItem;
    }

    /**
     * @param string $draftField
     * @return void
     */
    public function getSessionUniqueId(string $draftField)
    {
        if (!empty($draftField)) {
            $draftHashArray = explode(',', $draftField);
            foreach($draftHashArray as $draftHash) {
                $draftItem = $this->getDraftById($draftHash);
                if ($draftItem) {
                    $pfProductId = $draftItem->getData('printformer_product_id');
                    $productId = $draftItem->getData('product_id');
                    if(!empty($productId) && !empty($pfProductId)) {
                        $uniqueId = $this->sessionHelper->getSessionUniqueIdByProductId($productId, $pfProductId);
                        if (!isset($uniqueId)) {
                            $uniqueId = $this->sessionHelper->loadSessionUniqueId($productId, $pfProductId, $draftHash);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param ProductModel $configurableProduct
     * @param array $superAttributes
     * @return ProductModel|null
     */
    public function getChildProduct(ProductModel $configurableProduct, array $superAttributes)
    {
        $childProduct = $this->configurableProduct->getProductByAttributes($superAttributes, $configurableProduct);
        return $childProduct;
    }

    /**
     * @param array $lastUpdatedList
     * @return void
     */
    public function updateIdentifierByResponseArray(array $lastUpdatedList)
    {
        $connection = $this->resourceConnection->getConnection();

        $outdatedList = $this->getOutdatedList($connection);

        $updates = $this->findUpdates($outdatedList, $lastUpdatedList);

        $this->updateTables($updates, $connection);
    }

    /**
     * @param $connection
     * @return mixed
     */
    private function getOutdatedList($connection)
    {
        return $connection->fetchAll(
            $connection->select()
                ->from(['pp' => InstallSchema::TABLE_NAME_PRODUCT], ['id', 'identifier'])
                ->joinLeft([
                               'cpp' => UpgradeSchema::TABLE_NAME_CATALOG_PRODUCT_PRINTFORMER_PRODUCT
                           ], 'cpp.printformer_product_id = pp.id', ['identifier as cpp_identifier'])
                ->columns([
                              'pp_id' => 'pp.id',
                              'pp_name' => 'pp.name',
                              'pp_store_id' => 'pp.store_id',
                              'pp_intent' => 'pp.intent',
                              'pp_master_id' => 'pp.master_id',
                              'pp_identifier' => 'pp.identifier',
                              'cpp_id' => 'cpp.id',
                              'cpp_printformer_product_id' => 'cpp.printformer_product_id',
                              'cpp_product_id' => 'cpp.product_id',
                              'cpp_store_id' => 'cpp.store_id',
                              'cpp_intent' => 'cpp.intent',
                              'cpp_master_id' => 'cpp.master_id',
                              'cpp_identifier' => 'cpp.identifier'
                          ])
                ->where('pp.identifier = "" OR cpp.identifier = ""')
        );
    }

    /**
     * @param $outdatedList
     * @param $lastUpdatedList
     * @return array|array[]
     */
    private function findUpdates(
        $outdatedList,
        $lastUpdatedList
    )
    {

        //search for update-information in lastUpdatedList
        $updates = ['cpp' => [], 'pp' => []];

        if ( !empty($outdatedList) ) {
            // search for update-information in lastUpdatedList
            foreach ( $outdatedList as $outdatedEntry ) {
                foreach ( $lastUpdatedList['data'] as $product ) {
                    if ( !empty($outdatedEntry['pp_id']) ) {
                        if ( !empty($outdatedEntry['pp_master_id']) && ($outdatedEntry['pp_master_id'] == $product['id']) ) {
                            $updates['pp'][] = ['table' => 'printformer_product', 'identifier' => $product['identifier'], 'id' => (int)$outdatedEntry['pp_id']];
                        } elseif ( !empty($outdatedEntry['pp_name']) && ($outdatedEntry['pp_name'] == $product['name']) ) {
                            $updates['pp'][] = ['table' => 'printformer_product', 'identifier' => $product['identifier'], 'id' => (int)$outdatedEntry['pp_id']];
                        }
                    }

                    if ( !empty($outdatedEntry['cpp_id']) ) {
                        if ( !empty($outdatedEntry['cpp_master_id']) && ($outdatedEntry['cpp_master_id'] == $product['id']) ) {
                            $updates['cpp'][] = ['table' => 'catalog_product_printformer_product', 'identifier' => $product['identifier'], 'id' => (int)$outdatedEntry['cpp_id']];
                        } elseif ( !empty($outdatedEntry['pp_name']) && ($outdatedEntry['pp_name'] == $product['name']) ) {
                            $updates['cpp'][] = ['table' => 'catalog_product_printformer_product', 'identifier' => $product['identifier'], 'id' => (int)$outdatedEntry['cpp_id']];
                        }
                    }
                }
            }
        }

        return $updates;
    }

    /**
     * @param $updates
     * @param $connection
     * @return void
     */
    private function updateTables(
        $updates,
        $connection
    )
    {
        $connection->beginTransaction();

        $updatePrintformerStatement = $connection->prepare(
            "UPDATE " . InstallSchema::TABLE_NAME_PRODUCT
            . " SET identifier = :identifier, updated_at = CURTIME() WHERE id = :id");

        $updateProductStatement = $connection->prepare(
            "UPDATE "
            . UpgradeSchema::TABLE_NAME_CATALOG_PRODUCT_PRINTFORMER_PRODUCT
            . " SET identifier = :identifier WHERE id = :id"
        );

        try {
            foreach ( $updates['pp'] as $update ) {
                $updatePrintformerStatement->execute([
                                                         'identifier' => $update['identifier'],
                                                         'id' => $update['id']
                                                     ]);
            }
            foreach ( $updates['cpp'] as $update ) {
                $updateProductStatement->execute([
                                                     'identifier' => $update['identifier'],
                                                     'id' => $update['id']
                                                 ]);
            }
            $connection->commit();
        } catch ( Exception $e ) {
            $connection->rollBack();
        }
    }
}