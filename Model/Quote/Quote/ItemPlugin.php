<?php
namespace Rissc\Printformer\Model\Quote\Quote;

use Magento\Framework\Serialize\SerializerInterface;
use Rissc\Printformer\Setup\InstallSchema;

class ItemPlugin
{
    protected $_serializer;

    public function __construct(
        SerializerInterface $serializer
    )
    {
        $this->_serializer = $serializer;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartItemInterface $item
     * @param callable $proceed
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
                $value = $this->_serializer->unserialize($infoBuyRequest->getValue());
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
                $value = $this->_serializer->unserialize($infoBuyRequest->getValue());
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
}
