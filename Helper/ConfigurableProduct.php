<?php

namespace Rissc\Printformer\Helper;

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

/**
 * Class ConfigurableProduct
 * @package Rissc\Printformer\Helper
 */
class ConfigurableProduct extends AbstractHelper
{
    public const XML_PATH_INVENTORY_MANAGE_STOCK_CONFIG_ENABLED = 'cataloginventory/item_options/manage_stock';

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

    /**
     * ConfigurableProduct constructor.
     * @param Context $context
     * @param ConfigurableResource $resourceConfigurable
     * @param Configurable $configurable
     * @param Attribute $attributeFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        ConfigurableResource $resourceConfigurable,
        Configurable $configurable,
        Attribute $attributeFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->resourceConfigurable = $resourceConfigurable;
        $this->configurable = $configurable;
        $this->attributeFactory = $attributeFactory;
        $this->storeManager = $storeManager;
        //set store id
        try {
            $this->storeId = $this->storeManager->getStore()->getId();
        } catch (NoSuchEntityException $e) {
        }
        parent::__construct($context);
    }

    /**
     * @param $storeId
     */
    public function setStoreId($storeId): void
    {
        $this->storeId = $storeId;
    }

    /**
     * @return int
     */
    public function getStoreId(): int
    {
        if (!$this->storeId) {
            $this->setStoreId(Store::DEFAULT_STORE_ID);
        }
        return $this->storeId;
    }

    /**
     * @return boolean
     */
    public function isConfigManageStockEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_INVENTORY_MANAGE_STOCK_CONFIG_ENABLED,
            ScopeInterface::SCOPE_STORES,
            $this->getStoreId()
        );
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
     * @param $attributesInfo
     * @param $product
     * @param $storeId
     * @return array
     */
    public function getAllAvailableChildrenByConfigurable($attributesInfo, $product, $storeId): array
    {
        $this->configurable->getProductByAttributes($attributesInfo, $product);

        $productCollection = $this->configurable->getUsedProductCollection($product);

        //filter everything except color in $attributesInfo
        $attributesInfo = $this->removeColorAttributeFromAttributesList($attributesInfo);
        foreach ($attributesInfo as $attributeId => $attributeValue) {
            $productCollection->addAttributeToFilter($attributeId, $attributeValue);
        }

        $productCollection->addAttributeToSelect('*');
        $productCollection->addFieldToFilter('printformer_color_variation', ['neq' => '']);
        $productCollection->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);

        $productCollection->getSelect()->joinLeft(
            array('_store' => $productCollection->getResource()->getTable('catalog_product_website')),
            "_store.product_id = e.entity_id and _store.website_id=$storeId"
        );
        $productCollection->getSelect()->where('_store.website_id='.$storeId);

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

        $configManageStock = $this->isConfigManageStockEnabled();
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
        $availableVariants = [];
        $productsData = $productCollection->getData();
        foreach ($productsData as $productData){
            $availableVariants[] = $productData['printformer_color_variation'];
        }

        return $availableVariants;
    }
}