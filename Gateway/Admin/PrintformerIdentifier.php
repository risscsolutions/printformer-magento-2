<?php

namespace Rissc\Printformer\Gateway\Admin;

use Magento\Store\Model\ResourceModel\Store\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Customer\Model\CustomerFactory;

class PrintformerIdentifier
{
    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    protected $connection;

    /** @var StoreManagerInterface  */
    protected $_storeManager;

    /** @var CustomerFactory */
    protected $_customerFactory;

    /** @var CollectionFactory */
    protected $_storeCollectionFactory;

    /**
     * PrintformerIdentifier constructor.
     * @param StoreManagerInterface $storeManager
     * @param CustomerFactory $customerFactory
     * @param CollectionFactory $storeCollectionFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CustomerFactory $customerFactory,
        CollectionFactory $storeCollectionFactory
    )
    {
        $this->_storeManager = $storeManager;
        $this->_customerFactory = $customerFactory;
        $this->_storeCollectionFactory = $storeCollectionFactory;

        $customers = $customerFactory->create();
        $this->connection = $customers->getResource()->getConnection();
    }

    /**
     * @param int $storeId
     * @return bool
     */
    public function deletePrintformerIdentificationByStorId($storeId)
    {
        try {
            $this->connection->query("UPDATE `customer_entity` SET `printformer_identification` = NULL WHERE `store_id` = " . $storeId .";");

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param null $websiteId
     * @return bool
     */
    public function deletePrintformerIdentification($websiteId = null)
    {
        $collection = $this->_storeCollectionFactory->create();
        $result = false;

        if ($websiteId !== null) {
            $collection->addFieldToFilter('website_id', ['eq' => $websiteId]);
        }

        /** @var Store $store */
        foreach ($collection->getItems() as $store) {
            $result = $this->deletePrintformerIdentificationByStorId($store->getId());
        }

        return $result;
    }
}