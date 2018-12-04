<?php
namespace Rissc\Printformer\Helper;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * Class Catalog
 */
class Catalog
    extends AbstractHelper
{
    /** @var ProductFactory */
    protected $_productFactory;

    /** @var ProductResource */
    protected $_productResource;

    public function __construct(
        Context $context,
        ProductFactory $productFactory,
        ProductResource $productResource
    ) {
        $this->_productFactory = $productFactory;
        $this->_productResource = $productResource;

        parent::__construct($context);
    }

    /**
     * @param Product | int $product
     *
     * @return Product
     */
    public function prepareProduct($product)
    {
        if (is_numeric($product)) {
            $productModel = $this->_productFactory->create();
            $this->_productResource->load($productModel, $product);

            $product = $productModel;
        }

        return $product;
    }
}