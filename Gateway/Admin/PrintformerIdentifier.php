<?php

namespace Rissc\Printformer\Gateway\Admin;

use Magento\Customer\Model\CustomerFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class PrintformerIdentifier
 * @package Rissc\Printformer\Gateway\Admin
 */
class PrintformerIdentifier
{
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var CollectionFactory
     */
    protected $_storeCollectionFactory;

    /**
     * @var Attribute
     */
    protected $_eavAttribute;

    /**
     * PrintformerIdentifier constructor.
     * @param StoreManagerInterface $storeManager
     * @param CustomerFactory $customerFactory
     * @param CollectionFactory $storeCollectionFactory
     * @param Attribute $eavAttribute
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CustomerFactory $customerFactory,
        CollectionFactory $storeCollectionFactory,
        Attribute $eavAttribute
    ) {
        $this->_storeManager = $storeManager;
        $this->_customerFactory = $customerFactory;
        $this->_storeCollectionFactory = $storeCollectionFactory;
        $this->_eavAttribute = $eavAttribute;

        $customers = $customerFactory->create();
        $this->_connection = $customers->getResource()->getConnection();
    }

    /**
     * @param int $storeId
     * @return bool
     */
    public function deletePrintformerIdentificationByStoreId($storeId)
    {
        try {
            $attributeId = $attributeId = $this->_eavAttribute->getIdByCode('customer', 'printformer_identification');
            $this->_connection->query("UPDATE `customer_entity` as `main` 
            INNER JOIN `customer_entity_varchar` as `attr` ON `main`.`entity_id` = `attr`.`entity_id` 
            SET `main`.`printformer_identification` = NULL, `attr`.`value` = NULL 
            WHERE `main`.`store_id` = " . $storeId . " AND `attr`.`attribute_id` = " . $attributeId . ";");

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
            $result = $this->deletePrintformerIdentificationByStoreId($store->getId());
        }

        return $result;
    }
}
