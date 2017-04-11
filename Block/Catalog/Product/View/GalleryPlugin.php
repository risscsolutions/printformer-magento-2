<?php
namespace Rissc\Printformer\Block\Catalog\Product\View;

class GalleryPlugin extends Printformer
{
    /**
     * @param \Magento\Catalog\Block\Product\View\Gallery $gallery
     * @param unknown $result
     * @return string
     */
    public function afterGetGalleryImages(\Magento\Catalog\Block\Product\View\Gallery $gallery, $result)
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
