<?php

namespace Rissc\Printformer\Block\Wishlist\Customer\Wishlist\Item;

use Magento\Wishlist\Block\Customer\Wishlist\Item\Column;
use Rissc\Printformer\Helper\Config;
use Rissc\Printformer\Helper\Media;
use Rissc\Printformer\Setup\InstallSchema;
use Rissc\Printformer\Helper\Api\Url;

class ColumnPlugin
{
    /**
     * @var Media
     */
    protected $mediaHelper;

    /**
     * @var Url
     */
    protected $urlHelper;

    /**
     * @var Config
     */
    protected $config;

    /**
     * ColumnPlugin constructor.
     * @param Config $config
     * @param Url $urlHelper
     * @param Media $mediaHelper
     */
    public function __construct(
        Config $config,
        Url $urlHelper,
        Media $mediaHelper
    ) {
        $this->config = $config;
        $this->urlHelper = $urlHelper;
        $this->mediaHelper = $mediaHelper;
    }

    /**
     * Set image url for printformer item
     *
     * @param Column $subject
     * @param $result
     * @return mixed
     */
    public function afterGetImage(Column $subject, $result)
    {
        $item = $subject->getItem();
        if($item) {
            $option = $item->getOptionByCode(InstallSchema::COLUMN_NAME_DRAFTID);
            if($option) {
                $draftId = $option->getValue();
                if ($draftId) {
                    if($this->config->isV2Enabled()) {
                        $imageUrl = $this->mediaHelper->getImageUrl($draftId);
                    } else {
                        $imageUrl = $this->urlHelper->getThumbnail($draftId);
                    }
                    $result->setData('image_url', $imageUrl);
                }
            }
        }

        return $result;
    }
}
