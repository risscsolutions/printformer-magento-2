<?php

namespace Rissc\Printformer\Plugin\Quote\Quote;

use Rissc\Printformer\Setup\InstallSchema;
use Magento\Quote\Model\Quote\Item;

class ItemModel
{
    /**
     * @param Item $item
     * @param \Closure $proceed
     * @param $product
     * @return bool
     */
    public function aroundRepresentProduct(Item $item, \Closure $proceed, $product)
    {
        $result = $proceed($product);
        if ($result) {
            $itemDraftId = null;
            $itemOptions = $item->getOptionsByCode();
            if (isset($itemOptions['info_buyRequest'])
                && $itemOptions['info_buyRequest'] instanceof \Magento\Quote\Model\Quote\Item\Option
            ) {
                $infoBuyRequest = $itemOptions['info_buyRequest'];
                $value = $this->unserialize($infoBuyRequest->getValue());
                $itemDraftId = isset($value[InstallSchema::COLUMN_NAME_DRAFTID])
                    ? $value[InstallSchema::COLUMN_NAME_DRAFTID]
                    : null;
            }
            $productDraftId = null;
            $productOptions = $product->getCustomOptions();
            if (isset($productOptions['info_buyRequest'])
                && $productOptions['info_buyRequest'] instanceof \Magento\Catalog\Model\Product\Configuration\Item\Option
            ) {
                $infoBuyRequest = $productOptions['info_buyRequest'];
                $value = $this->unserialize($infoBuyRequest->getValue());
                $productDraftId = isset($value[InstallSchema::COLUMN_NAME_DRAFTID])
                    ? $value[InstallSchema::COLUMN_NAME_DRAFTID]
                    : null;
            }
            if ($itemDraftId != $productDraftId) {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * @param string $string
     * @return mixed
     */
    private function unserialize($string)
    {
        if($this->isJson($string)) {
            return json_decode($string, true);
        } else {
            return unserialize($string);
        }
    }

    /**
     * @param $string
     * @return bool
     */
    private function isJson($string) {
        json_decode($string);

        return json_last_error() == JSON_ERROR_NONE;
    }
}
