<?php

namespace Rissc\Printformer\Ui;

use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\AddFieldToCollectionInterface;
use Magento\Ui\DataProvider\AddFilterToCollectionInterface;
use Rissc\Printformer\Model\Product;
use Rissc\Printformer\Model\ResourceModel\Product\Collection;
use Rissc\Printformer\Model\ResourceModel\Product\CollectionFactory;
use Magento\Backend\Model\Session as BackendSession;

class DataProvider extends AbstractDataProvider
{
    /**
     * Printformer Templates collection
     *
     * @var Collection
     */
    protected $collection;

    /**
     * @var AddFieldToCollectionInterface[]
     */
    protected $addFieldStrategies;

    /**
     * @var AddFilterToCollectionInterface[]
     */
    protected $addFilterStrategies;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var BackendSession
     */
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
     * @param AddFieldToCollectionInterface[] $addFieldStrategies
     * @param AddFilterToCollectionInterface[] $addFilterStrategies
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
        if ($storeId > 0 || $this->_session->getPrintformerTemplatesStoreId() === null) {
            $this->_session->setPrintformerTemplatesStoreId($storeId);
        }
        $storeId = intval($this->_session->getPrintformerTemplatesStoreId());

        if ($storeId > 0) {
            $collection->addFieldToFilter('store_id', $storeId);
        }

        $itemArray = [];
        /** @var Product $item */
        foreach ($collection->getItems() as $item) {
            $item->setTemplateId($item->getId());

            $itemArray[] = $item->toArray();
        }

        $returnArray = [
            'totalRecords' => $collection->getSize(),
            'items' => $itemArray
        ];

        return $returnArray;
    }
}
