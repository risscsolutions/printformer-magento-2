<?php

namespace Rissc\Printformer\Block\Checkout\Cart\Item;

use Magento\Checkout\Block\Cart\Item\Renderer;
use Rissc\Printformer\Helper\Url;
use Rissc\Printformer\Helper\Config;
use Rissc\Printformer\Block\Checkout\Cart\Renderer as ReplaceRenderer;

class RendererPlugin
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
     * RendererPlugin constructor.
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
     * @param Renderer $renderer
     * @param          $result
     * @return string
     */
    public function afterGetImage(Renderer $renderer, $result)
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

    /**
     * @param Renderer|ReplaceRenderer $renderer
     * @param                          $result
     * @return string
     */
    public function afterGetProductUrl($renderer, $result)
    {
        return $renderer->getConfigureUrl();
    }
}
