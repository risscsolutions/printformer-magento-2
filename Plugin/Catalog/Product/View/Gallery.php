<?php

namespace Rissc\Printformer\Plugin\Catalog\Product\View;

use Magento\Catalog\Block\Product\View\Gallery as SubjectGallery;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Rissc\Printformer\Block\Catalog\Product\View\Printformer as PrintformerBlock;
use Rissc\Printformer\Helper\Media;
use Rissc\Printformer\Helper\Product as PrintformerProductHelper;
use Rissc\Printformer\Helper\Session;
use Rissc\Printformer\Helper\Config;


class Gallery
{
    /**
     * @var PrintformerBlock
     */
    protected $printformerBlock;

    /**
     * @var bool
     */
    protected $draftImageCreated = [];

    /**
     * @var Media
     */
    protected $mediaHelper;

    /**
     * @var PrintformerProductHelper $printformerProductHelper
     */
    private PrintformerProductHelper $printformerProductHelper;
    private Session $sessionHelper;
    private Config $configHelper;
    private Configurable $configurable;

    /**
     * Gallery constructor.
     * @param Media $mediaHelper
     * @param PrintformerBlock $printformerBlock
     * @param PrintformerProductHelper $printformerProductHelper
     */
    public function __construct(
        Media $mediaHelper,
        PrintformerBlock $printformerBlock,
        PrintformerProductHelper $printformerProductHelper,
        Session $sessionHelper,
        Config $configHelper,
        Configurable $configurable
    ) {
        $this->mediaHelper = $mediaHelper;
        $this->printformerBlock = $printformerBlock;
        $this->printformerProductHelper = $printformerProductHelper;
        $this->sessionHelper = $sessionHelper;
        $this->configHelper = $configHelper;
        $this->configurable = $configurable;
    }

    /**
     * If printformer images have been loaded, check if one of them is the main image
     * @param SubjectGallery $gallery
     * @param \Closure $proceed
     * @param \Magento\Framework\DataObject $image
     * @return bool
     */
    public function aroundIsMainImage(SubjectGallery $gallery, \Closure $proceed, $image)
    {
        if(count($this->draftImageCreated) > 0) {
            return $image->getIsMainImage();
        }
        return $proceed($image);
    }

    /**
     * Function to load base image on initial page  and product image loading operation
     *
     * @param SubjectGallery $gallery
     * @param $result
     * @return mixed
     */
    public function afterGetGalleryImages(SubjectGallery $gallery, $result)
    {
        $product = $gallery->getProduct();
        $draftIds = $this->getDraftIds($product->getId(), $product->getStore()->getId(), $product->getTypeId());

        $this->mediaHelper->loadDraftImagesToNonChildCollection($draftIds, $result);

        return $result;
    }

    /**
     *
     *
     * @param $productId
     * @param $storeId
     * @return array
     */
    private function getDraftIds($productId, $storeId)
    {
        $draftIds = [];
        $catalogProductPrintformerProducts = $this->printformerBlock->getCatalogProductPrintformerProducts($productId, $storeId);

        foreach($catalogProductPrintformerProducts as $catalogProductPrintformerProduct) {
            $pfProduct = $catalogProductPrintformerProduct->getPrintformerProduct();
            $draftId = $this->printformerProductHelper->searchAndLoadDraftId($pfProduct);
            if (!$this->printformerProductHelper->draftIsAlreadyUsedInCart($draftId)) {
                if (!empty($draftId)) {
                    $draftIds[] = $draftId;
                }
            }
        }

        return $draftIds;
    }
}
