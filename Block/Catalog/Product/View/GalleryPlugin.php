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
    protected $draftImageCreated = false;

    /**
     * @var Media
     */
    protected $mediaHelper;

    /**
     * @var UrlHelper
     */
    protected $urlHelper;

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
     * @param Gallery $gallery
     * @param $result
     * @return mixed
     */
    public function afterGetGalleryImages(Gallery $gallery, $result)
    {
        if ($this->getImagePreviewUrl()) {
            $result->addItem(new \Magento\Framework\DataObject([
                'id' => 0,
                'small_image_url' => $this->getImagePreviewUrl(),
                'medium_image_url' => $this->getImagePreviewUrl(),
                'large_image_url' => $this->getImagePreviewUrl()
            ]));
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
     * @return string
     */
    public function getImagePreviewUrl()
    {
        $url = null;
        if ($this->config->isUseImagePreview() && $this->getDraftId()) {
            if($this->config->isV2Enabled()) {
                try {
                    if (!$this->draftImageCreated) {
                        $draft = $this->printformerApi->draftProcess($this->getDraftId());
                        $jpgImg = $this->printformerApi->getThumbnail($this->getDraftId(), $draft->getUserIdentifier(), $this->config->getImagePreviewWidth(), $this->config->getImagePreviewHeight(), 1);
                        $printformerImage = $jpgImg['content'];

                        $imageFilePath = $this->mediaHelper->getImageFilePath($this->getDraftId());

                        $image = imagecreatefromstring($printformerImage);
                        imageAlphaBlending($image, true);
                        imageSaveAlpha($image, true);
                        imagejpeg($image, $imageFilePath, 90);
                        imagedestroy($image);

                        $this->draftImageCreated = true;
                    }

                    $url = $this->mediaHelper->getImageUrl($this->getDraftId());
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
