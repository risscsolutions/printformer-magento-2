<?php

namespace Rissc\Printformer\Block\Catalog\Product\View;

use Magento\Catalog\Block\Product\View\Gallery;
use Psr\Log\LoggerInterface;
use Rissc\Printformer\Helper\Config as ConfigHelper;
use Rissc\Printformer\Helper\Api as PrintformerApi;
use Rissc\Printformer\Block\Catalog\Product\View\Printformer as PrintformerBlock;
use Rissc\Printformer\Helper\Media;
use Rissc\Printformer\Helper\Api\Url as UrlHelper;

class GalleryPlugin
{
    /**
     * @var ConfigHelper
     */
    protected $config;

    /**
     * @var PrintformerApi
     */
    protected $printformerApi;

    /**
     * @var PrintformerBlock
     */
    protected $printformerBlock;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var bool
     */
    protected $draftImageCreated = [];

    /**
     * @var Media
     */
    protected $mediaHelper;

    /**
     * @var UrlHelper
     */
    protected $urlHelper;

    /**
     * @var array
     */
    protected $printformerDraft = null;

    /**
     * GalleryPlugin constructor.
     * @param ConfigHelper $config
     * @param Media $mediaHelper
     * @param UrlHelper $urlHelper
     * @param PrintformerApi $printformerApi
     * @param Printformer $printformerBlock
     * @param LoggerInterface $logger
     */
    public function __construct(
        ConfigHelper $config,
        Media $mediaHelper,
        UrlHelper $urlHelper,
        PrintformerApi $printformerApi,
        PrintformerBlock $printformerBlock,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->mediaHelper = $mediaHelper;
        $this->urlHelper = $urlHelper;
        $this->printformerApi = $printformerApi;
        $this->printformerBlock = $printformerBlock;
        $this->logger = $logger;
    }

    /**
     * If printformer images have been loaded, check if one of them is the main image
     * @param Gallery $gallery
     * @param \Closure $proceed
     * @param \Magento\Framework\DataObject $image
     * @return bool
     */
    public function aroundIsMainImage(Gallery $gallery, \Closure $proceed, $image)
    {
        if(count($this->draftImageCreated) > 0) {
            return $image->getIsMainImage();
        }
        return $proceed($image);
    }

    /**
     * @param Gallery $gallery
     * @param \Magento\Framework\Data\Collection $result
     * @return \Magento\Framework\Data\Collection
     */
    public function afterGetGalleryImages(Gallery $gallery, $result)
    {
        if ($this->getImagePreviewUrl()) {
            if($this->config->isV2Enabled()) {
                $printformerDraft = $this->getPrintformerDraft();
                $pages = isset($printformerDraft['pages']) ? $printformerDraft['pages'] : 1;

                for($i = 0; $i < $pages; $i++) {
                    try {
                        $result->addItem(new \Magento\Framework\DataObject([
                            'id' => $i,
                            'small_image_url' => $this->getImagePreviewUrl(($i + 1)),
                            'medium_image_url' => $this->getImagePreviewUrl(($i + 1)),
                            'large_image_url' => $this->getImagePreviewUrl(($i + 1)),
                            'is_main_image' => ($i == 0)
                        ]));
                    } catch(\Exception $e) {
                        $this->logger->error($e->getMessage());
                        $this->logger->error($e->getTraceAsString());
                    }
                }
            } else {
                try {
                    $result->addItem(new \Magento\Framework\DataObject([
                        'id' => 0,
                        'small_image_url' => $this->getImagePreviewUrl(),
                        'medium_image_url' => $this->getImagePreviewUrl(),
                        'large_image_url' => $this->getImagePreviewUrl(),
                        'is_main_image' => true
                    ]));
                } catch(\Exception $e) {
                    $this->logger->error($e->getMessage());
                    $this->logger->error($e->getTraceAsString());
                }
            }
        }
        return $result;
    }

    /**
     * @return int
     */
    public function getDraftId()
    {
        return $this->printformerBlock->getDraftId();
    }

    /**
     * @return array
     */
    public function getPrintformerDraft()
    {
        if($this->printformerDraft === null) {
            $this->printformerDraft = $this->printformerApi->getPrintformerDraft($this->getDraftId());
        }
        return $this->printformerDraft;
    }

    /**
     * @param int $page
     * @return null|string
     */
    public function getImagePreviewUrl($page = 1)
    {
        $url = null;
        if ($this->config->isUseImagePreview() && $this->getDraftId()) {
            if($this->config->isV2Enabled()) {
                try {
                    if (!isset($this->draftImageCreated[$page])) {
                        $draft = $this->printformerApi->draftProcess($this->getDraftId());
                        $jpgImg = $this->printformerApi->getThumbnail($this->getDraftId(), $draft->getUserIdentifier(), $this->config->getImagePreviewWidth(), $this->config->getImagePreviewHeight(), $page);
                        $printformerImage = $jpgImg['content'];

                        $imageFilePath = $this->mediaHelper->getImageFilePath($this->getDraftId(), $page);

                        $image = imagecreatefromstring($printformerImage);
                        imageAlphaBlending($image, true);
                        imageSaveAlpha($image, true);
                        imagejpeg($image, $imageFilePath, 90);
                        imagedestroy($image);

                        $this->draftImageCreated[$page] = true;
                    }

                    $url = $this->mediaHelper->getImageUrl($this->getDraftId(), $page);
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                    $this->logger->error($e->getTraceAsString());
                }
            } else {
                $url = $this->urlHelper->getThumbImgUrl($this->getDraftId());
            }
        }
        return $url;
    }
}
