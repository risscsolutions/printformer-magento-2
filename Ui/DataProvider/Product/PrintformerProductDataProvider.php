<?php

namespace Rissc\Printformer\Ui\DataProvider\Product;

use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Rissc\Printformer\Model\ResourceModel\Product\CollectionFactory;
use Magento\Backend\Model\Session as BackendSession;

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

    /** @var BackendSession */
    protected $_session;

    /**
     * Construct
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface $request
     * @param BackendSession $session
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
        BackendSession $session,
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
        $this->_session = $session;
    }

    /**
     * @return array
     */
    public function getData()
    {
        $collection = $this->getCollection();
        $collection->addFieldToSelect('*');
        $storeId = intval($this->request->getParam('store', 0));
        if ($storeId > 0 || $this->_session->getPrintformerProductStoreId() === null) {
            $this->_session->setPrintformerProductStoreId($storeId);
        }
        $storeId = intval($this->_session->getPrintformerProductStoreId());
        $collection->addFieldToFilter('store_id', $storeId);
        return $collection->toArray();
    }
}
