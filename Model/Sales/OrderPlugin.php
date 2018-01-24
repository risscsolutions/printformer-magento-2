<?php
namespace Rissc\Printformer\Model\Sales;

use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Rissc\Printformer\Gateway\Admin\Draft;
use Rissc\Printformer\Setup\InstallSchema;
use Magento\Sales\Api\Data\OrderInterface;
use Rissc\Printformer\Helper\Api as ApiHelper;

class OrderPlugin extends \Rissc\Printformer\Model\PrintformerPlugin
{
    /** @var ApiHelper */
    protected $_apiHelper;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Registry $coreRegistry,
        \Rissc\Printformer\Model\DraftFactory $draftFactory,
        \Rissc\Printformer\Gateway\Admin\Draft $printformerDraft,
        \Rissc\Printformer\Helper\Config $config,
        \Rissc\Printformer\Helper\Session $sessionHelper,
        ApiHelper $apiHelper
    )
    {
        $this->_apiHelper = $apiHelper;

        parent::__construct($logger, $cartRepositoryInterface, $storeManager, $messageManager, $coreRegistry, $draftFactory, $printformerDraft, $config, $sessionHelper);
    }

    /**
     * Set printformer data to order item
     *
     * @param OrderInterface $subject
     */
    public function beforePlace(OrderInterface $subject)
    {
        try {
            if(!$subject->getQuote()) {
                $quote = $this->cartRepositoryInterface->get($subject->getQuoteId());
            } else {
                $quote = $subject->getQuote();
            }
            $allItems = $quote->getAllItems();
            if(count($allItems) > 0)
            {
                /** @var Order\Item $item */
                foreach ($subject->getAllItems() as $key => $item)
                {
                    /** @var Quote\Item $quoteItem */
                    $quoteItem = $allItems[$key];
                    $draftId = $quoteItem->getData(InstallSchema::COLUMN_NAME_DRAFTID);
                    if ($draftId)
                    {
                        $item->setData(InstallSchema::COLUMN_NAME_DRAFTID, $draftId);
                    }
                    $storeId = $quoteItem->getData(InstallSchema::COLUMN_NAME_STOREID);
                    if ($storeId)
                    {
                        $item->setData(InstallSchema::COLUMN_NAME_STOREID, $storeId);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * Check status for payed order
     *
     * @param OrderInterface $subject
     * @return OrderInterface
     */
    public function afterPlace(OrderInterface $subject)
    {
        try {
            $draftIds = [];
            foreach ($subject->getAllItems() as $item) {
                $draftId = $item->getData(InstallSchema::COLUMN_NAME_DRAFTID);
                if ($draftId) {
                    $this->draftFactory
                        ->create()
                        ->load($draftId, 'draft_id')
                        ->setOrderItemId($item->getId())
                        ->save();
                }

                if ($item->getPrintformerOrdered() || !$item->getPrintformerDraftid()) {
                    continue;
                }
                $draftIds[] = $item->getPrintformerDraftid();
            }

            if ($subject->getStatus() == $this->config->getOrderStatus()) {
                if ($this->config->getProcessingType() == Draft::DRAFT_PROCESSING_TYPE_SYNC && !$this->config->isV2Enabled()) {
                    $this->printformerDraft->setDraftOrdered($subject);
                } else {
                    $this->_apiHelper->setAsyncOrdered($draftIds);
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return $subject;
    }
}
