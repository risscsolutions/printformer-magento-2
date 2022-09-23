<?php

namespace Rissc\Printformer\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\Product\Type\Model;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResource;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ProductFactory;
use Rissc\Printformer\Helper\Config;

/**
 * Class ConfigurableProduct
 * @package Rissc\Printformer\Helper
 */
class ConfigurableProduct extends AbstractHelper
{
    /**
     * @var ConfigurableResource
     */
    private $resourceConfigurable;

    /**
     * @var Configurable
     */
    private $configurable;

    /**
     * @var Attribute
     */
    private $attributeFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var int
     */
    protected $storeId;
    private ProductRepositoryInterface $productRepository;
    private ProductFactory $productFactory;
    private Config $configHelper;

    /**
     * ConfigurableProduct constructor.
     * @param Context $context
     * @param ConfigurableResource $resourceConfigurable
     * @param Configurable $configurable
     * @param Attribute $attributeFactory
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Context $context,
        ConfigurableResource $resourceConfigurable,
        Configurable $configurable,
        Attribute $attributeFactory,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        ProductFactory $productFactory,
        Config $configHelper
    ) {
        $this->resourceConfigurable = $resourceConfigurable;
        $this->configurable = $configurable;
        $this->attributeFactory = $attributeFactory;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->configHelper = $configHelper;
        //set store id
        try {
            $this->storeId = $this->storeManager->getStore()->getId();
        } catch (NoSuchEntityException $e) {
        }
        parent::__construct($context);
    }

    /**
     * @param $simpleProductId
     * @return array|string[]
     */
    public function getConfigurableBySimple($simpleProductId)
    {
        $parentId = [];
        try {
            return $this->resourceConfigurable->getParentIdsByChild($simpleProductId);
        } catch(\Exception $e) {
        }

        return $parentId;
    }

    /**
     * Get Parent Product Resource from by child-product-id
     *
     * @param $simpleProductId
     * @param $storeId
     * @return ProductInterface|null
     */
    public function getFirstConfigurableBySimpleProductId($simpleProductId, $storeId)
    {
        $product = null;

        try {
            $parentIds = $this->resourceConfigurable->getParentIdsByChild($simpleProductId);
            if(isset($parentIds[0])){
                $product = $this->productRepository->getById($parentIds[0], false, $storeId);
            }
        } catch(\Exception $e) {
        }

        return $product;
    }

    /**
     * @param $attributesList
     * @return mixed
     */
    public function removeColorAttributeFromAttributesList($attributesList)
    {
        /** @var Attribute $attribute */
        $attributesColorId = $this->attributeFactory->getCollection()
            ->addFieldToFilter('attribute_code', ['eq' => 'color'])
            ->getFirstItem()
            ->getData('attribute_id');

        if (!empty($attributesColorId)){
            unset($attributesList[(int)$attributesColorId]);
        }

        return $attributesList;
    }

    /**
     * Get all available children of configurable product with attributesInfo(preselection), excluding color-attribute
     * and applying all required filters
     *
     * if products are available without printformer_color_variation id then return true to simply exlude the return
     * array in implemented method
     * @param $attributesInfo
     * @param $product
     * @param $storeId
     * @return array | boolean
     */
    public function getAllAvailableChildrenByConfigurable($attributesInfo, $product, $storeId)
    {
        $this->configurable->getProductByAttributes($attributesInfo, $product);

        $productCollection = $this->configurable->getUsedProductCollection($product);

        //filter everything except color in $attributesInfo
        $attributesInfo = $this->removeColorAttributeFromAttributesList($attributesInfo);
        foreach ($attributesInfo as $attributeId => $attributeValue) {
            $productCollection->addAttributeToFilter($attributeId, $attributeValue);
        }

        $productCollection->addAttributeToSelect('*');
        $productCollection->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);

        $productCollection->getSelect()->joinLeft(
            array('_store' => $productCollection->getResource()->getTable('catalog_product_website')),
            "_store.product_id = e.entity_id and _store.website_id=$storeId"
        );

        //outcomment next line to test without individual stock entry's (cataloginventory_stock_status.website_id != 0)
        $defaultInventoryId = 0;

        $productCollection->getSelect()->joinLeft(
            array('_inv' => $productCollection->getResource()->getTable('cataloginventory_stock_status')),
            "_inv.product_id = e.entity_id and _inv.website_id=$defaultInventoryId"
        );
        $productCollection->addAttributeToSelect('_inv.stock_status');

        //store specific but also only store-id 0 available (test with $storeId = 0;)
        $productCollection->getSelect()->joinLeft(
            array('_inv_item' => $productCollection->getResource()->getTable('cataloginventory_stock_item')),
            "_inv_item.product_id = e.entity_id and _inv_item.website_id=$defaultInventoryId"
        );
        $productCollection->addAttributeToSelect('_inv_item.manage_stock');

        $configManageStock = $this->configHelper->isConfigManageStockEnabled();
        //nested where for core-config-manage-stock filter handling
        if ($configManageStock) {
            $productCollection->getSelect()->where('
                (use_config_manage_stock = 1
                AND _inv_item.manage_stock = 1
                AND _inv_item.is_in_stock = 1
                AND _inv_item.qty >= _inv_item.min_sale_qty
                AND _inv_item.qty > 0
            ');

            $productCollection->getSelect()->where('
                use_config_manage_stock = 1
                AND _inv_item.manage_stock = 0
            ');

            $productCollection->getSelect()->orWhere('
                use_config_manage_stock = 0)
            ');
        } else {
            $productCollection->getSelect()->where('
                (use_config_manage_stock = 0
                AND _inv_item.manage_stock = 1
                AND _inv_item.is_in_stock = 1
                AND _inv_item.qty >= _inv_item.min_sale_qty
                AND _inv_item.qty > 0
            ');

            $productCollection->getSelect()->orWhere('
                use_config_manage_stock = 0
                AND _inv_item.manage_stock = 0
            ');

            $productCollection->getSelect()->orWhere('
                use_config_manage_stock = 1)
            ');
        }

        //prepare printformer_color_variation-ids of collection
        $productsDataWithoutVariationIdFilter = $productCollection->getData();
        $resultAvailableVariants = [];

        if (!empty($productsDataWithoutVariationIdFilter)) {
            $productCollection->addFieldToFilter('printformer_color_variation', ['neq' => '']);
            //overwrite with filter if products are available *current $productsData not empty
            //Todo:reset collection data????
            $productCollection->resetData();
            $productsDataWithVariationIdFilter = $productCollection->getData();


            foreach ($productsDataWithVariationIdFilter as $productData){
                $resultAvailableVariants[] = $productData['printformer_color_variation'];
            }

            if(empty($resultAvailableVariants)){
                $resultAvailableVariants = true;
            }
        } else {
            $resultAvailableVariants = false;
        }

        return $resultAvailableVariants;
    }

    /**
     * @param $superAttributes
     * @param $parentProductId
     * @return \Magento\Catalog\Model\Product
     */
    public function getChildProductBySuperAttributes($superAttributes, $parentProductId): \Magento\Catalog\Model\Product
    {
        $parentProduct = $this->productFactory->create()->load($parentProductId);
        return $this->configurable->getProductByAttributes($superAttributes, $parentProduct);
    }
}