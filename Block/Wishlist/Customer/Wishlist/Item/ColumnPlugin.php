<?php

namespace Rissc\Printformer\Block\Wishlist\Customer\Wishlist\Item;

use Magento\Wishlist\Block\Customer\Wishlist\Item\Column;
use Rissc\Printformer\Setup\InstallSchema;
use Rissc\Printformer\Block\Catalog\Product\View\Printformer;

class ColumnPlugin extends Printformer
{
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
                    $imageUrl = $this->urlHelper->getThumbImgUrl($draftId);
                    $result->setData('image_url', $imageUrl);
                }
            }
        }

        return $result;
    }
}
