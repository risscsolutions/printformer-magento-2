<?php
namespace Rissc\Printformer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Rissc\Printformer\Setup\InstallSchema;

class SetOrderItemDraftId implements ObserverInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Rissc\Printformer\Helper\Config
     */
    protected $config;

    /**
     * @var \Rissc\Printformer\Model\DraftFactory
     */
    protected $draftFactory;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Rissc\Printformer\Helper\Config $config
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Rissc\Printformer\Helper\Config $config,
        \Rissc\Printformer\Model\DraftFactory $draftFactory
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->draftFactory = $draftFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->config->isEnabled()) {
            return;
        }
        try {
            /**
             * @var \Magento\Quote\Model\Quote $quote
             */
            $quote = $observer->getData('quote');
            /**
             * @var \Magento\Sales\Model\Order $order
             */
            $order = $observer->getData('order');
            foreach ($order->getAllItems() as $item) {
                $quoteItem = $quote->getItemById($item->getQuoteItemId());
                $draftIds = $quoteItem->getData(InstallSchema::COLUMN_NAME_DRAFTID);
                $storeId = $quoteItem->getData(InstallSchema::COLUMN_NAME_STOREID);
                $item->setData(InstallSchema::COLUMN_NAME_DRAFTID, $draftIds);
                $item->setData(InstallSchema::COLUMN_NAME_STOREID, $storeId);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
