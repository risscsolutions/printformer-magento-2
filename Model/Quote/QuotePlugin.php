<?php
namespace Rissc\Printformer\Model\Quote;

use Rissc\Printformer\Setup\InstallSchema;
use Magento\Quote\Api\Data as QuoteData;

class QuotePlugin extends \Rissc\Printformer\Model\PrintformerPlugin
{
    /**
     * Set printformer data for new quote item
     *
     * @param QuoteData\CartInterface $subject
     * @param $result
     * @return QuoteData\CartItemInterface|string
     */
    public function afterAddProduct(QuoteData\CartInterface $subject, $result)
    {
        return is_string($result) ? $result : $this->setPrintformerData($result);
    }

    /**
     * Set printformer data for updated quote item
     *
     * @param QuoteData\CartInterface $subject
     * @param $result
     * @return QuoteData\CartItemInterface|string
     */
    public function afterUpdateItem(QuoteData\CartInterface $subject, $result)
    {
        return is_string($result) ? $result : $this->setPrintformerData($result);
    }

    /**
     * Set printformer data
     *
     * @param QuoteData\CartItemInterface $item
     * @return QuoteData\CartItemInterface
     */
    protected function setPrintformerData(QuoteData\CartItemInterface $item)
    {
        try {
            if (isset($item->getBuyRequest()[InstallSchema::COLUMN_NAME_DRAFTID])) {
                $storeId = $this->storeManager->getStore()->getId();
                $draftId = $item->getBuyRequest()[InstallSchema::COLUMN_NAME_DRAFTID];

                $item->setData(InstallSchema::COLUMN_NAME_STOREID, $storeId);
                $item->setData(InstallSchema::COLUMN_NAME_DRAFTID, $draftId);

                $this->sessionHelper->unsDraftId($item->getProduct()->getId(), $storeId);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return $item;
    }
}
