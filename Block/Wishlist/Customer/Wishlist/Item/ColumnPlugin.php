<?php
namespace Rissc\Printformer\Block\Wishlist\Customer\Wishlist\Item;

use Rissc\Printformer\Setup\InstallSchema;

class ColumnPlugin extends \Rissc\Printformer\Block\Catalog\Product\View\Printformer
{
    /**
     * Set image url for printformer item
     *
     * @param \Magento\Wishlist\Block\Customer\Wishlist\Item\Column $subject
     * @param $result
     * @return mixed
     */
    public function afterGetImage(\Magento\Wishlist\Block\Customer\Wishlist\Item\Column $subject, $result)
    {
        $item = $subject->getItem();
        if($item)
        {
            $option = $item->getOptionByCode(InstallSchema::COLUMN_NAME_DRAFTID);
            if($option)
            {
                $draftId = $option->getValue();
                if ($draftId)
                {
                    $imageUrl = $this->urlHelper->getThumbImgUrl($draftId);
                    $result->setData('image_url', $imageUrl);
                }
            }
        }

        return $result;
    }
}
