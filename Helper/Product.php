<?php

namespace Rissc\Printformer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Rissc\Printformer\Model\ProductFactory;
use Rissc\Printformer\Model\ResourceModel\Product as ResourceProduct;

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
     * Product constructor.
     * @param ProductFactory $productFactory
     * @param ResourceProduct $resource
     * @param ResourceConnection $resourceConnection
     * @param Context $context
     */
    public function __construct(
        ProductFactory $productFactory,
        ResourceProduct $resource,
        ResourceConnection $resourceConnection,
        Context $context
    ) {
        $this->productFactory = $productFactory;
        $this->resource = $resource;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context);
    }

    /**
     * @param $productId
     * @return \Rissc\Printformer\Model\Product[]
     */
    public function getPrintformerProducts($productId)
    {
        $printformerProducts = [];

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from('catalog_product_printformer_product')->where('product_id = ?', $productId);
        $result = $connection->fetchAll($select);

        foreach($result as $row) {
            $printformerProduct = $this->productFactory->create();
            $this->resource->load($printformerProduct, $row['printformer_product_id']);

            if($printformerProduct->getId()) {
                $printformerProducts[] = $printformerProduct;
            }
        }

        return $printformerProducts;
    }

    /**
     * @param $productId
     * @return array
     */
    public function getPrintformerProductsArray($productId)
    {
        $printformerProducts = [];
        foreach($this->getPrintformerProducts($productId) as $printformerProduct) {
            $printformerProducts[] = $printformerProduct->getData();
        }
        return $printformerProducts;
    }
}