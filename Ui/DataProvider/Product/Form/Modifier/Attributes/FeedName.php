<?php

namespace Rissc\Printformer\Ui\DataProvider\Product\Form\Modifier\Attributes;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;
use Rissc\Printformer\Helper\Config;

class FeedName extends AbstractModifier
{
    const DATA_GROUP = 'printformer-product-feed';
    const FEED_ATTRIBUTE = 'feed_name';

    /**
     * @var ArrayManager
     */
    protected $arrayManager;

    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @param ArrayManager $arrayManager
     * @param LocatorInterface $locator
     * @param Config $configHelper
     */
    public function __construct(
        ArrayManager     $arrayManager,
        LocatorInterface $locator,
        Config           $configHelper
    )
    {
        $this->arrayManager = $arrayManager;
        $this->locator = $locator;
        $this->configHelper = $configHelper;
    }

    /**
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $storeId = $this->locator->getProduct()->getStoreId();
        $enabled = $this->configHelper->isEnableFeedIdentifier($storeId);
        if ($enabled) {
            $path = $this->arrayManager->findPath(self::FEED_ATTRIBUTE, $meta, null, 'children');
            $meta = $this->arrayManager->set(
                "{$path}/arguments/data/config/additionalClasses",
                $meta,
                'rissc_feed_name'
            );
        } else {
            unset($meta[self::DATA_GROUP]);
        }
        return $meta;
    }
}
