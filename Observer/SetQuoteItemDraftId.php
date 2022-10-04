<?php
namespace Rissc\Printformer\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Rissc\Printformer\Helper\Config;
use Rissc\Printformer\Helper\Session;
use Rissc\Printformer\Setup\InstallSchema;

class SetQuoteItemDraftId implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var Session
     */
    protected $sessionHelper;

    /**
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param Config $configHelper
     * @param Session $sessionHelper
     */
    public function __construct(
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        Config $configHelper,
        Session $sessionHelper
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
        $storeId = $this->storeManager->getStore()->getId();
        if (!$this->configHelper->isEnabled($storeId)) {
            return;
        }
        try {
            /**
             *@var Item $item
             */
            $item = $observer->getEvent()->getData('quote_item');
            if (!($item instanceof Item)
                || !isset($item->getBuyRequest()[InstallSchema::COLUMN_NAME_DRAFTID])) {
                return;
            }

            if ($this->configHelper->useChildProduct($item->getProductType())) {
                $childProduct = $item->getChildren();
                if (is_array($childProduct) && !empty($childProduct)) {
                    $item = $childProduct[0];
                }
            }

            $storeId = $this->storeManager->getStore()->getId();
            $draftIds = $item->getBuyRequest()[InstallSchema::COLUMN_NAME_DRAFTID];
            $item = $this->configHelper->setDraftsOnItemType($item, $draftIds);
            $item->setData(InstallSchema::COLUMN_NAME_STOREID, $storeId);
            $this->sessionHelper->unsetDraftIds($item->getProduct()->getId(), $storeId);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
