<?php

namespace Rissc\Printformer\Block\Catalog\Product\View;

use Magento\Catalog\Block\Product\View\Gallery;

class GalleryPlugin extends Printformer
{
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
     * @return string
     */
    public function isUseImagePreview()
    {
        return $this->configHelper->isUseImagePreview();
    }

    /**
     * @return string
     */
    public function getImagePreviewUrl()
    {
        $url = null;
        if ($this->isUseImagePreview() && $this->getDraftId()) {
            $url = $this->urlHelper->getThumbImgUrl($this->getDraftId());
        }
        return $url;
    }
}
