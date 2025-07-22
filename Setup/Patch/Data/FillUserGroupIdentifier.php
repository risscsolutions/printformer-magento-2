<?php

namespace Rissc\Printformer\Setup\Patch\Data;

use Rissc\Printformer\Helper\Api;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;

class FillUserGroupIdentifier implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var Api
     */
    private $api;

    /**
     * @var CollectionFactory
     */
    private $groupCollectionFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Api                      $api
     * @param CollectionFactory        $groupCollectionFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Api $api,
        CollectionFactory $groupCollectionFactory

    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->api = $api;
        $this->groupCollectionFactory = $groupCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $groupCollection = $this->groupCollectionFactory->create();
        foreach ($groupCollection as $group) {
            $userGroupItentifier = $this->api->createUserGroup();
            $group->setData('identifier', $userGroupItentifier);
            $group->save();
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }
}
