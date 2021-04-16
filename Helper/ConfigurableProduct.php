<?php

namespace Rissc\Printformer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResource;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\Product\Type\Model;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

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
     * ConfigurableProduct constructor.
     * @param Context $context
     * @param ConfigurableResource $resourceConfigurable
     * @param Configurable $configurable
     * @param Attribute $attributeFactory
     */
    public function __construct(
        Context $context,
        ConfigurableResource $resourceConfigurable,
        Configurable $configurable,
        Attribute $attributeFactory
    ) {
        $this->resourceConfigurable = $resourceConfigurable;
        $this->configurable = $configurable;
        $this->attributeFactory = $attributeFactory;
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
     * Get all children of configurable product with attributesInfo(preselection), excluding color-attribute
     *
     * @param $attributesInfo
     * @param $product
     * @return ConfigurableResource\Product\Collection
     */
    public function getAllChildrenByConfigurable($attributesInfo, $product)
    {
        $this->configurable->getProductByAttributes($attributesInfo, $product);
        $productCollection = $this->configurable->getUsedProductCollection($product);

        //filter everything except color in $attributesInfo
        $attributesInfo = $this->removeColorAttributeFromAttributesList($attributesInfo);
        foreach ($attributesInfo as $attributeId => $attributeValue) {
            $productCollection->addAttributeToFilter($attributeId, $attributeValue);
        }

        return $productCollection;
    }
}