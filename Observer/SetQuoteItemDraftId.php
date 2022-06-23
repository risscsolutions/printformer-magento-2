<?php
namespace Rissc\Printformer\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote\Item;
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
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->configHelper->isEnabled()) {
            return;
        }
        try {
            /**
             *@var Item $item
             */
            $item = $observer->getEvent()->getData('quote_item');
            if (!($item instanceof Item)
                || !isset($item->getBuyRequest()['printformer_draftid'])) {
                return;
            }
            $storeId = $this->storeManager->getStore()->getId();
            $draftIds = $item->getBuyRequest()['printformer_draftid'];

            $item = $this->configHelper->setDraftsOnItemType($item, $draftIds);
            $item->setData(InstallSchema::COLUMN_NAME_STOREID, $storeId);

            $this->sessionHelper->unsetDraftId($item->getProduct()->getId(), $storeId);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
