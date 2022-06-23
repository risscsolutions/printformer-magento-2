<?php

namespace Rissc\Printformer\Block\Checkout\Cart;

use Magento\Catalog\Block\Product\Image;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\View\Element\Template\Context;
use Magento\Catalog\Helper\Product\Configuration;
use Magento\Checkout\Model\Session;
use Magento\Catalog\Block\Product\ImageBuilder;
use Magento\Framework\Url\Helper\Data;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Module\Manager;
use Magento\Checkout\Block\Cart\Item\Renderer as ItemRenderer;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Message\InterpretationStrategyInterface;
use Rissc\Printformer\Helper\Api\Url;
use Rissc\Printformer\Helper\Config;
use Rissc\Printformer\Helper\Media;

class Renderer extends ItemRenderer
{
    /**
     * @var Url
     */
    protected $apiUrlHelper;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var Media
     */
    protected $mediaHelper;

    /**
     * Renderer constructor.
     * @param Url $apiUrlHelper
     * @param Config $configHelper
     * @param Media $mediaHelper
     * @param Context $context
     * @param Configuration $productConfig
     * @param Session $checkoutSession
     * @param ImageBuilder $imageBuilder
     * @param Data $urlHelper
     * @param ManagerInterface $messageManager
     * @param PriceCurrencyInterface $priceCurrency
     * @param Manager $moduleManager
     * @param InterpretationStrategyInterface $messageInterpretationStrategy
     * @param array $data
     */
    public function __construct(
        Url $apiUrlHelper,
        Config $configHelper,
        Media $mediaHelper,
        Context $context,
        Configuration $productConfig,
        Session $checkoutSession,
        ImageBuilder $imageBuilder,
        Data $urlHelper,
        ManagerInterface $messageManager,
        PriceCurrencyInterface $priceCurrency,
        Manager $moduleManager,
        InterpretationStrategyInterface $messageInterpretationStrategy,
        array $data = []
    ) {
        $this->apiUrlHelper = $apiUrlHelper;
        $this->configHelper = $configHelper;
        $this->mediaHelper = $mediaHelper;
        parent::__construct($context, $productConfig, $checkoutSession, $imageBuilder, $urlHelper, $messageManager, $priceCurrency, $moduleManager, $messageInterpretationStrategy, $data);
    }

    /**
     * @return string
     */
    public function getConfigureUrl()
    {
        return $this->getUrl(
            'checkout/cart/configure',
            [
                'id' => $this->getItem()->getId(),
                'product_id' => $this->getItem()->getProduct()->getId()
            ]
        );
    }

    /**
     * @param $product
     * @param $imageId
     * @param $attributes
     * @return Image
     */
    public function getImage($product, $imageId, $attributes = []): Image
    {
        $result = parent::getImage($product, $imageId, $attributes);
        $draftIds = $this->configHelper->getDraftIdsFromSpecificItemType($this->getItem());

        if ($draftIds) {
            $imageUrl = $this->mediaHelper->loadThumbsImageUrlByDraftId($draftIds);
            if (isset($imageUrl)) {
                $result->setImageUrl($imageUrl);
            }
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getProductUrl()
    {
        return $this->getConfigureUrl();
    }
}