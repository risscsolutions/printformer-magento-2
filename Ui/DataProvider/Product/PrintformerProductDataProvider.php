<?php

namespace Rissc\Printformer\Ui\DataProvider\Product;

use Magento\Framework\App\RequestInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Rissc\Printformer\Model\ResourceModel\Product\CollectionFactory;

class PrintformerProductDataProvider extends AbstractDataProvider
{
    /**
     * Printformer Product collection
     * @var \Rissc\Printformer\Model\ResourceModel\Product\Collection
     */
    protected $collection;

    /**
     * @var \Magento\Ui\DataProvider\AddFieldToCollectionInterface[]
     */
    protected $addFieldStrategies;

    /**
     * @var \Magento\Ui\DataProvider\AddFilterToCollectionInterface[]
     */
    protected $addFilterStrategies;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * Construct
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface $request
     * @param \Magento\Ui\DataProvider\AddFieldToCollectionInterface[] $addFieldStrategies
     * @param \Magento\Ui\DataProvider\AddFilterToCollectionInterface[] $addFilterStrategies
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        array $addFieldStrategies = [],
        array $addFilterStrategies = [],
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->addFieldStrategies = $addFieldStrategies;
        $this->addFilterStrategies = $addFilterStrategies;
        $this->request = $request;
    }

    /**
     * @return array
     */
    public function getData()
    {
        $collection = $this->getCollection();
        $collection->addFieldToSelect('*');
        $storeId = (int)$this->request->getParam('current_store_id', 0);
        $collection->addFieldToFilter('store_id', $storeId);
        return $collection->toArray();
    }
}
