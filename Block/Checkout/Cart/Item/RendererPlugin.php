<?php
namespace Rissc\Printformer\Block\Checkout\Cart\Item;

use \Rissc\Printformer\Helper as Helper;

class RendererPlugin
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
     * @param \Magento\Checkout\Block\Cart\Item\Renderer $renderer
     * @param unknown $result
     * @return string
     */
    public function afterGetImage(\Magento\Checkout\Block\Cart\Item\Renderer $renderer, $result)
    {
        $draftId = $renderer->getItem()->getPrintformerDraftid();
        if ($draftId && $this->isUseImagePreview()) {
            $result->setImageUrl($this->urlHelper->getThumbImgUrl($draftId));
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
