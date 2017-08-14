<?php

namespace Rissc\Printformer\Checkout\CustomerData;

use Magento\Checkout\CustomerData\DefaultItem;
use Magento\Quote\Api\Data\CartItemInterface;
use Rissc\Printformer\Helper\Url;
use Rissc\Printformer\Helper\Config;

class DefaultItemPlugin
{
    /**
     * @var Url
     */
    protected $urlHelper;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * DefaultItemPlugin constructor.
     * @param Url $urlHelper
     * @param Config $configHelper
     */
    public function __construct(
        Url $urlHelper,
        Config $configHelper
    ) {
        $this->urlHelper = $urlHelper;
        $this->configHelper = $configHelper;
    }

    /**
     * @param DefaultItem $defaultItem
     * @param \Closure $proceed
     * @param CartItemInterface $item
     * @return mixed
     */
    public function aroundGetItemData(DefaultItem $defaultItem, \Closure $proceed, CartItemInterface $item)
    {
        $result = $proceed($item);
        $draftId = $item->getPrintformerDraftid();
        if ($draftId && $this->isUseImagePreview()) {
            $result['product_image']['src'] = $this->urlHelper->getThumbImgUrl($item->getPrintformerDraftid());
        }
        return $result;
    }

    /**
     * @return string
     */
    public function isUseImagePreview()
    {
        return $this->configHelper->isUseImagePreview();
    }
}
