<?php
namespace Rissc\Printformer\Model\Quote\Quote;

use Magento\Framework\App\ObjectManager;
use Rissc\Printformer\Setup\InstallSchema;

class ItemPlugin
{
    /**
     * @param \Magento\Quote\Api\Data\CartItemInterface $item
     * @param \Closure $proceed
     * @param $product
     * @return bool
     */
    public function aroundRepresentProduct(\Magento\Quote\Api\Data\CartItemInterface $item, \Closure $proceed, $product)
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
     *
     * @return mixed
     */
    protected function unserialize($string)
    {
        if(class_exists('Magento\Framework\Serialize\Serializer')) {
            $objm = ObjectManager::getInstance();
            $serializer = $objm->get('Magento\Framework\Serialize\Serializer');
            return $serializer->unserialize($string);
        } else {
            return unserialize($string);
        }
    }
}
