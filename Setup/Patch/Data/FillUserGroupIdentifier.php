<?php

namespace Rissc\Printformer\Setup\Patch\Data;

use Rissc\Printformer\Helper\Api;
use Rissc\Printformer\Helper\Config;
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
     * @var Config
     */
    private  $config;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Api                      $api
     * @param CollectionFactory        $groupCollectionFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Api $api,
        CollectionFactory $groupCollectionFactory,
        Config $config

    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->api = $api;
        $this->groupCollectionFactory = $groupCollectionFactory;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        if (!$this->config->isEnabled() || !$this->config->isUserCustomerGroupEnabled()) {
            return $this;
        }

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
