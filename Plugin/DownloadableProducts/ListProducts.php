<?php

namespace Rissc\Printformer\Plugin\DownloadableProducts;

use Magento\Downloadable\Block\Customer\Products\ListProducts as ListProductsSubject;
use Magento\Downloadable\Model\Link\Purchased\Item;
use Rissc\Printformer\Helper\DownloadableProduct as DownloadableProductHelper;

class ListProducts
{
    /**
     * @var DownloadableProductHelper
     */
    protected $downloadableProductHelper;

    /**
     * ListProducts constructor.
     *
     * @param   DownloadableProductHelper  $downloadableProductHelper
     */
    public function __construct(
        DownloadableProductHelper $downloadableProductHelper
    ) {
        $this->downloadableProductHelper = $downloadableProductHelper;
    }

    /**
     * Try to get Printformer download url
     *
     * @param   ListProductsSubject  $subject
     * @param   \Closure             $proceed
     * @param   Item                 $item
     *
     * @return string
     */
    public function aroundGetDownloadUrl(
        ListProductsSubject $subject,
        \Closure $proceed,
        Item $item
    ) {
        $url = $proceed($item);
        $downloadableProductUrl = null;
        if ($this->downloadableProductHelper->tryGetPrintformerPdfUrl($item,
            $downloadableProductUrl)
        ) {
            $url = $downloadableProductUrl;
        }

        return $url;
    }
}
