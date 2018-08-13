<?php

namespace Rissc\Printformer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Downloadable\Model\Link\Purchased\Item as PurchasedItem;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Order\ItemFactory;
use Magento\Sales\Model\ResourceModel\Order\Item as ItemResource;

class DownloadableProduct extends AbstractHelper
{
    /**
     * @var ItemFactory
     */
    protected $itemFactory;

    /**
     * @var ItemResource
     */
    protected $itemResource;

    /**
     * DownloadableProduct constructor.
     * @param ItemFactory $itemFactory
     * @param ItemResource $itemResource
     * @param Context $context
     */
    public function __construct(
        ItemFactory $itemFactory,
        ItemResource $itemResource,
        Context $context
    ) {
        $this->itemFactory = $itemFactory;
        $this->itemResource = $itemResource;
        parent::__construct($context);
    }

    /**
     * @param PurchasedItem $purchasedItem
     * @param string $url
     * @return bool
     */
    public function tryGetPrintformerPdfUrl(PurchasedItem $purchasedItem, &$url)
    {
        $item = $this->itemFactory->create();
        $this->itemResource->load($item, $purchasedItem->getOrderItemId());

        try {
            if ($item->getPrintformerDraftid() != null) {
                $url = $this->_getUrl('printformer/get/pdf', [
                    'draft_id' => $item->getPrintformerDraftid(),
                    'quote_id' => $item->getOrder()->getQuoteId()
                ]);
                return true;
            }
        } catch(\Exception $e) {
            $this->_logger->error($e->getMessage());
            $this->_logger->error($e->getTraceAsString());
        }

        return false;
    }
}