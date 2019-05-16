<?php
namespace Rissc\Printformer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Rissc\Printformer\Setup\InstallSchema;

class SetQuoteItemDraftId implements ObserverInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Rissc\Printformer\Helper\Config
     */
    protected $configHelper;

    /**
     * @var \Rissc\Printformer\Helper\Session
     */
    protected $sessionHelper;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Rissc\Printformer\Helper\Config $configHelper
     * @param \Rissc\Printformer\Helper\Session $sessionHelper
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Rissc\Printformer\Helper\Config $configHelper,
        \Rissc\Printformer\Helper\Session $sessionHelper
    ) {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->configHelper = $configHelper;
        $this->sessionHelper = $sessionHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->configHelper->isEnabled()) {
            return;
        }
        try {
            /**
             *@var \Magento\Quote\Model\Quote\Item
             */
            $item = $observer->getEvent()->getData('quote_item');
            if (!($item instanceof \Magento\Quote\Model\Quote\Item)
                || !isset($item->getBuyRequest()['printformer_draftid'])) {
                return;
            }
            $storeId = $this->storeManager->getStore()->getId();
            $draftIds = $item->getBuyRequest()['printformer_draftid'];

            $item->setData(InstallSchema::COLUMN_NAME_STOREID, $storeId);
            $item->setData(InstallSchema::COLUMN_NAME_DRAFTID, $draftIds);

            $this->sessionHelper->unsDraftId($item->getProduct()->getId(), $storeId);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
