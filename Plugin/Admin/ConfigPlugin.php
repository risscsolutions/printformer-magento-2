<?php
namespace Rissc\Printformer\Plugin\Admin;
use Magento\Catalog\Model\Product;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Rissc\Printformer\Setup\UpgradeData;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Setup\EavSetupFactory;

class ConfigPlugin
{
    /**
     * @var \Magento\Framework\Setup\ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var CategorySetupFactory
     */
    private $categorySetupFactory;

    /**
     * UpdateGiftMessageAttribute constructor.
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     * @param CategorySetupFactory $categorySetupFactory
     */
    public function __construct(
        \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup,
        CategorySetupFactory $categorySetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->categorySetupFactory = $categorySetupFactory;
    }


    public function aroundSave(
        \Magento\Config\Model\Config $subject,
        \Closure $proceed
    ) {

        $result = $proceed;
        $section = $subject->getSection();
        // your custom logic
        if($section == 'printformer')
        {
            if(isset($subject->getGroups()['general']['fields']['delete_feed_identifier']['value'])) {
                $feedIdentifier = $subject->getGroups()['general']['fields']['delete_feed_identifier']['value'];
                $this->apply($feedIdentifier);
            };
        }
        return $proceed();
    }

    /**
     * {@inheritdoc}
     */
    public function apply($feedIdentifier = 1)
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        /** @var \Magento\Catalog\Setup\CategorySetup $categorySetup */
        $categorySetup = $this->categorySetupFactory->create(['setup' => $this->moduleDataSetup]);
        $categorySetup->updateAttribute(Product::ENTITY, 'feed_identifier', 'is_visible', $feedIdentifier);
        $categorySetup->updateAttribute(Product::ENTITY, 'feed_name', 'is_visible', $feedIdentifier);

        $this->moduleDataSetup->getConnection()->endSetup();
    }
}