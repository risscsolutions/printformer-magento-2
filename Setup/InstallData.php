<?php
namespace Rissc\Printformer\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    const MODULE_NAMESPACE = 'Rissc';
    const MODULE_NAME = 'Printformer';

    /**
     * EAV setup
     *
     * @var EavSetup
     */
    private $eavSetup;

    /**
     * Init
     *
     * @param EavSetup $eavSetup
     */
    public function __construct(EavSetup $eavSetup)
    {
        $this->eavSetup = $eavSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /**
         * Add attributes to the eav/attribute
         */
        $this->eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'printformer_enabled',
            [
                'group' => 'Printformer',
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'label' => 'Enable Printformer',
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
        $this->eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'printformer_product',
            [
                'group' => 'Printformer',
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'label' => 'Printformer Product',
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
    }
}
