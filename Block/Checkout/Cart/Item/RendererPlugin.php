<?php

namespace Rissc\Printformer\Block\Checkout\Cart\Item;

use Magento\Checkout\Block\Cart\Item\Renderer;
use Rissc\Printformer\Helper\Api\Url;
use Rissc\Printformer\Helper\Config;
use Rissc\Printformer\Block\Checkout\Cart\Renderer as ReplaceRenderer;
use Rissc\Printformer\Helper\Media;

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
     * @var Media
     */
    protected $mediaHelper;

    /**
     * RendererPlugin constructor.
     * @param Url $urlHelper
     * @param Config $configHelper
     * @param Media $mediaHelper
     */
    public function __construct(
        Url $urlHelper,
        Config $configHelper,
        Media $mediaHelper
    ) {
        $this->urlHelper = $urlHelper;
        $this->configHelper = $configHelper;
        $this->mediaHelper = $mediaHelper;
    }

    /**
     * @param Renderer $renderer
     * @param          $result
     * @return string
     */
    public function afterGetImage(Renderer $renderer, $result)
    {
        $draftId = $renderer->getItem()->getPrintformerDraftid();
        if ($draftId && $this->configHelper->isUseImagePreview()) {
            if($this->configHelper->isV2Enabled()) {
                $result->setImageUrl($this->mediaHelper->getImageUrl($draftId));
            } else {
                $result->setImageUrl($this->urlHelper->getThumbnail($draftId));
            }
        }
        return $result;
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
