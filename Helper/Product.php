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

    public function getPrintformerProducts($productId)
    {
        $printformerProducts = [];

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from('catalog_product_printformer_product')->where('product_id = ?', $productId);
        $result = $connection->fetchAll($select);

        $i = 0;
        foreach($result as $row) {
            $printformerProduct = $this->productFactory->create();
            $this->resource->load($printformerProduct, $row['id']);

            if($printformerProduct->getId()) {
                $printformerProducts[$i] = $printformerProduct->getData();
                $printformerProducts[$i]['record_id'] = $i;
                $i++;
            }
        }

        return $printformerProducts;
    }
}