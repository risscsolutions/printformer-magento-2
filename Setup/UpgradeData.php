<?php

namespace Rissc\Printformer\Setup;

use Magento\Customer\Model\Customer;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Rissc\Printformer\Gateway\Admin\Product;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * UpgradeData constructor.
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Statement_Exception
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $connection = $setup->getConnection();

        if(version_compare($context->getVersion(), '100.0.1', '<')) {
            /** @var EavSetup $eavSetup */
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'printformer_upload_product',
                [
                    'group' => 'Printformer',
                    'type' => 'int',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Printformer Upload Product',
                    'input' => 'select',
                    'class' => '',
                    'source' => 'Rissc\Printformer\Model\Product\Source',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => false,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => true,
                    'unique' => false,
                    'apply_to' => ''
                ]
            );

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'printformer_upload_enabled',
                [
                    'group' => 'Printformer',
                    'type' => 'int',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Enable Printformer Upload',
                    'input' => 'select',
                    'class' => '',
                    'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => 0,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => true,
                    'unique' => false,
                    'apply_to' => ''
                ]
            );

        }

        if(version_compare($context->getVersion(), '100.1.6', '<')) {
            $_attributesArray = [
                Product::PF_ATTRIBUTE_ENABLED,
                Product::PF_ATTRIBUTE_PRODUCT,
                Product::PF_ATTRIBUTE_UPLOAD_ENABLED,
                Product::PF_ATTRIBUTE_UPLOAD_PRODUCT
            ];

            $result = $connection->query("
                SELECT `attribute_id`, `source_model`
                FROM " . $setup->getTable("eav_attribute") . "
                WHERE `attribute_code` IN ('" . implode("', '", $_attributesArray) . "');
            ");

            $regex = 'Rissc.*\\\\Printformer.*\\\\Model\\\\Product\\\\Source';
            while($row = $result->fetch())
            {
                if(preg_match('/' . $regex . '/i', $row['source_model'], $match))
                {
                    $classExplode = explode('\\', $match[0]);
                    $namespace = $classExplode[0];
                    $module = $classExplode[1];

                    if($namespace != InstallData::MODULE_NAMESPACE)
                    {
                        $classExplode[0] = InstallData::MODULE_NAMESPACE;
                    }

                    if($module != InstallData::MODULE_NAME)
                    {
                        $classExplode[1] = InstallData::MODULE_NAME;
                    }
                    $connection->query("
                        UPDATE " . $setup->getTable("eav_attribute") . "
                        SET `source_model` = '" . implode('\\', $classExplode) . "'
                        WHERE `attribute_code` = " . $row['attribute_id'] . ";
                    ");
                }
            }
        }

        if(version_compare($context->getVersion(), '100.1.11', '<')) {
            /** @var EavSetup $eavSetup */
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'printformer_upload_enabled');
            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'printformer_upload_product');

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'printformer_capabilities',
                [
                    'group' => 'Printformer',
                    'type' => 'text',
                    'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                    'label' => 'Printformer Capabilities',
                    'input' => 'multiselect',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => 0,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => true,
                    'unique' => false,
                    'apply_to' => '',
                    'option' => [
                        'values' => [
                            'Editor',
                            'Personalizations',
                            'Upload'
                        ]
                    ]
                ]
            );
        }

        if(version_compare($context->getVersion(), '100.1.13', '<')) {
            /** @var EavSetup $eavSetup */
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'printformer_capabilities');

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'printformer_capabilities',
                [
                    'group' => 'Printformer',
                    'type' => 'text',
                    'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                    'label' => 'Printformer Capabilities',
                    'input' => 'multiselect',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => 0,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => true,
                    'unique' => false,
                    'apply_to' => '',
                    'option' => [
                        'values' => [
                            'Editor',
                            'Personalizations',
                            'Upload',
                            'Upload and Editor'
                        ]
                    ]
                ]
            );
        }

        if(version_compare($context->getVersion(), '100.2.30', '>')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            $eavSetup->addAttribute(
                'customer',
                'printformer_identification',
                [
                    'group' => 'general',
                    'type' => 'varchar',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Printformer Identification',
                    'input' => 'text',
                    'class' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => 0,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => true,
                    'unique' => true,
                    'apply_to' => ''
                ]
            );
        }

        if(version_compare($context->getVersion(), '100.3.8', '<')) {
            /**
             * Split intents in printformer_product table - every product/intent will be an own row
             */
            $select = $connection->select()->from('printformer_product');
            $result = $connection->fetchAll($select);

            $insertData = [];

            $i = 0;
            foreach($result as $row) {
                foreach(explode(',', $row['intent']) as $intent) {
                    $insertData[$i] = $row;
                    unset($insertData[$i]['id']);
                    $insertData[$i]['intent'] = $intent;
                    $i++;
                }
            }

            $connection->beginTransaction();
            $connection->delete('printformer_product');
            $connection->insertMultiple('printformer_product', $insertData);
            $connection->commit();
        }

        if(version_compare($context->getVersion(), '100.3.9', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'printformer_enabled');
            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'printformer_product');
            $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'printformer_capabilities');
        }

        if(version_compare($context->getVersion(), '100.6.11', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            $eavSetup->updateAttribute(Customer::ENTITY, 'printformer_identification', 'is_visible', false);
        }

        $setup->endSetup();
    }
}
