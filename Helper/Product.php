<?php

namespace Rissc\Printformer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Rissc\Printformer\Model\ProductFactory;
use Rissc\Printformer\Model\ResourceModel\Product as ResourceProduct;
use Magento\Framework\App\RequestInterface;

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
     * Product constructor.
     * @param ProductFactory $productFactory
     * @param ResourceProduct $resource
     * @param ResourceConnection $resourceConnection
     * @param Context $context
     * @param RequestInterface $request
     */
    public function __construct(
        ProductFactory $productFactory,
        ResourceProduct $resource,
        ResourceConnection $resourceConnection,
        Context $context,
        RequestInterface $request
    ) {
        $this->productFactory = $productFactory;
        $this->resource = $resource;
        $this->resourceConnection = $resourceConnection;
        $this->_request = $request;

        parent::__construct($context);
    }

    /**
     * @param     $productId
     * @param int $storeId
     *
     * @return array
     */
    public function getPrintformerProducts($productId, $storeId = 0)
    {
        $printformerProducts = [];

        foreach($this->getCatalogProductPrintformerProductsData($productId, $storeId) as $row) {
            $printformerProduct = $this->productFactory->create();
            $this->resource->load($printformerProduct, $row['printformer_product_id']);

            if($printformerProduct->getId()) {
                $printformerProducts[] = $printformerProduct;
            }
        }

        return $printformerProducts;
    }

    /**
     * @param     $productId
     * @param int $storeId
     *
     * @return array
     */
    protected function getCatalogProductPrintformerProductsData($productId, $storeId = 0)
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from('catalog_product_printformer_product')
            ->where('product_id = ?', $productId)
            ->where('store_id = ?', $storeId);

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
        $result = $this->getCatalogProductPrintformerProductsData($productId, $storeId);

        foreach($result as &$row) {
            $printformerProduct = $this->productFactory->create();
            $this->resource->load($printformerProduct, $row['printformer_product_id']);

            if($printformerProduct->getId()) {
                $id = $row['id'];
                $row = array_merge($row, $printformerProduct->getData());
                $row['id'] = $id;
            }
        }

        return $result;
    }

    /**
     * @param     $productId
     * @param int $storeId
     *
     * @return array
     */
    public function getCatalogProductPrintformerProducts($productId, $storeId = 0)
    {
        $catalogProductPrintformerProducts = [];

        foreach($this->getCatalogProductPrintformerProductsData($productId, $storeId) as $i => $row) {
            $catalogProductPrintformerProducts[$i] = new DataObject();
            $catalogProductPrintformerProducts[$i]->setCatalogProductPrintformerProduct(new DataObject($row));

            $printformerProduct = $this->productFactory->create();
            $this->resource->load($printformerProduct, $row['printformer_product_id']);
            $catalogProductPrintformerProducts[$i]->setPrintformerProduct($printformerProduct);
        }

        return $catalogProductPrintformerProducts;
    }

    /**
     * @param     $productId
     * @param int $storeId
     *
     * @return array
     */
    public function getPrintformerProductsArray($productId, $storeId = 0)
    {
        $printformerProducts = [];
        foreach($this->getPrintformerProducts($productId, $storeId) as $printformerProduct) {
            $printformerProducts[] = $printformerProduct->getData();
        }
        return $printformerProducts;
    }
}