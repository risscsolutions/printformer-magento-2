<?php

namespace Rissc\Printformer\Plugin\Wishlist\Customer\Wishlist\Item;

use Magento\Wishlist\Block\Customer\Wishlist\Item\Column as SubjectColumn;
use Rissc\Printformer\Helper\Config;
use Rissc\Printformer\Helper\Media;
use Rissc\Printformer\Setup\InstallSchema;
use Rissc\Printformer\Helper\Api\Url;

class Column
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
     * Column constructor.
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
     * @param SubjectColumn $subject
     * @param $result
     * @return mixed
     */
    public function afterGetImage(SubjectColumn $subject, $result)
    {
        $item = $subject->getItem();
        if($item) {
            $option = $item->getOptionByCode(InstallSchema::COLUMN_NAME_DRAFTID);
            if($option) {
                $draftField = $option->getValue();
                if ($draftField) {
                    $drafts = explode(',', $draftField ?? '');
                    foreach ($drafts as $draftId) {
                        $imageUrl = $this->mediaHelper->getImageUrl($draftId);
                        $result->setData('image_url', $imageUrl);
                    }
                }
            }
        }

        return $result;
    }
}