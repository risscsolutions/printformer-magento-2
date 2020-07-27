<?php
namespace Rissc\Printformer\Helper;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * Class PrintformerProductAttributes
 * @package Rissc\Printformer\Helper
 */
class PrintformerProductAttributes
    extends AbstractHelper
{
    /** @var ProductFactory */
    protected $productFactory;

    /** @var ProductResource */
    protected $productResource;

    /** @var Product */
    protected $currentSimpleProduct;

    /**
     * PrintformerProductAttributes constructor.
     * @param Context $context
     * @param ProductFactory $productFactory
     * @param ProductResource $productResource
     */
    public function __construct(
        Context $context,
        ProductFactory $productFactory,
        ProductResource $productResource
    ) {
        $this->productFactory = $productFactory;
        $this->productResource = $productResource;
        parent::__construct($context);
    }

    /**
     * @param $params
     * @return array
     */
    public function mergeFeedIdentifier($params)
    {
        if ($this->loadProductResource()){
            $productAttribute = $this->productResource->getAttribute('feed_identifier');
            $optionAttribute = $productAttribute->getIsVisible();

            if($optionAttribute == 1){
                $feedIdentifier = $productAttribute->getFrontend()->getValue($this->currentSimpleProduct);
                if(!empty($feedIdentifier)){
                    $params = array_merge($params, ['feedIdentifier' => $feedIdentifier]);
                }
            }
        }

        return $params;
    }

    /**
     * Get id to load to resource from request
     *
     * @param null $productId
     * @return bool
     */
    private function loadProductResource($productId = null)
    {
        if (!$productId) {
            $simpleProductId = $this->_request->getParam('product_id');
        } else {
            $simpleProductId = $productId;
        }
        if ($simpleProductId){
            $this->currentSimpleProduct = $this->productFactory->create();
            $this->productResource->load($this->currentSimpleProduct, $simpleProductId);
            return true;
        }

        return false;
    }
}