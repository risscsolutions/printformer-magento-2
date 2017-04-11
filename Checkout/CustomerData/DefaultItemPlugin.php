<?php
namespace Rissc\Printformer\Checkout\CustomerData;

use \Rissc\Printformer\Helper as Helper;

class DefaultItemPlugin
{
    /**
     * @var \Rissc\Printformer\Helper\Url
     */
    protected $urlHelper;

    /**
     * @var \Rissc\Printformer\Helper\Config
     */
    protected $configHelper;

    /**
     * @param \Rissc\Printformer\Helper\Url $urlHelper
     * @param \Rissc\Printformer\Helper\Config $configHelper
     */
    public function __construct(
        Helper\Url $urlHelper,
        Helper\Config $configHelper
    ) {
        $this->urlHelper = $urlHelper;
        $this->configHelper = $configHelper;
    }

    /**
     * @param \Magento\Checkout\CustomerData\DefaultItem $defaultItem
     * @param callable $proceed
     * @param \Magento\Quote\Api\Data\CartItemInterface $item
     * @return mixed
     */
    public function aroundGetItemData(
        \Magento\Checkout\CustomerData\DefaultItem $defaultItem,
        \Closure $proceed,
        \Magento\Quote\Api\Data\CartItemInterface $item)
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
