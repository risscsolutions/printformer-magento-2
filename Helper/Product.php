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
     * @param $productId
     * @param int $storeId
     * @param bool $includeDefaultStoreInWhereSelect
     * @param bool $subordinateSimpleProducts
     * @return array
     */
    protected function getCatalogProductPrintformerProductsData($productId, int $storeId = 0, bool $includeDefaultStoreInWhereSelect = true, bool $subordinateSimpleProducts = false)
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from('catalog_product_printformer_product');

        if ($includeDefaultStoreInWhereSelect && $storeId !== 0){
            $select->where("store_id IN (". 0 . "," . $storeId .")");
        } else {
            $select->where('store_id = ?', intval($storeId));
        }

        if (!empty($subordinateSimpleProducts)){
            array_unshift($subordinateSimpleProducts, $productId);
            $select->where("product_id IN (" . implode(',', $subordinateSimpleProducts) . ")");
        } else {
            $select->where('product_id = ?', intval($productId));
        }

        return $connection->fetchAll($select);
    }

    /**
     * @param $productId
     * @param int $storeId
     * @param bool $subordinateSimpleProducts
     * @return array
     */
    public function getPrintformerProducts($productId, int $storeId = 0, bool $subordinateSimpleProducts = false): array
    {
        $printformerProducts = [];

        foreach($this->getCatalogProductPrintformerProductsData($productId, $storeId, true, $subordinateSimpleProducts) as $row) {
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
     * @param string $masterId
     * @return array
     */
    public function getCatalogProductPrintformerProductsByMasterId($masterId)
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from('catalog_product_printformer_product')
            ->where('master_id = ?', $masterId);
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
     * @param $productId
     * @param int $storeId
     * @return array
     */
    public function getCatalogProductPrintformerProducts($productId, $storeId = 0): array
    {
        $catalogProductPrintformerProducts = [];

        foreach($this->getCatalogProductPrintformerProductsData($productId, $storeId) as $i => $row) {
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