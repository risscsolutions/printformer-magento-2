<?php

namespace Rissc\Printformer\Plugin\Quote;

use Closure;
use Exception;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\Quote as SubjectQuote;
use Rissc\Printformer\Helper\Session;
use Rissc\Printformer\Setup\InstallSchema;
use Psr\Log\LoggerInterface;
use Rissc\Printformer\Helper\Api as ApiHelper;
use Rissc\Printformer\Helper\Config as ConfigHelper;
use Rissc\Printformer\Helper\Cart as CartHelper;
use Magento\Framework\Registry;

class QuoteModel
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ApiHelper
     */
    protected $apiHelper;

    /**
     * @var Registry $registry
     */
    private Registry $registry;

    /**
     * @var CartHelper
     */
    private CartHelper $cartHelper;

    /**
     * @var ConfigHelper
     */
    private ConfigHelper $configHelper;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Session $session
     * @param LoggerInterface $logger
     * @param ApiHelper $apiHelper
     * @param Registry $registry
     * @param CartHelper $cartHelper
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Session $session,
        LoggerInterface $logger,
        ApiHelper $apiHelper,
        Registry $registry,
        CartHelper $cartHelper,
        ConfigHelper $configHelper
    )
    {
        $this->storeManager = $storeManager;
        $this->session = $session;
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->registry = $registry;
        $this->cartHelper = $cartHelper;
        $this->configHelper = $configHelper;
    }

    /**
     * @param SubjectQuote $subject
     * @param Closure $proceed
     * @param Product $product
     * @param null|float|DataObject $buyRequest
     * @param $processMode
     * @return Item|mixed|string
     */
    public function aroundAddProduct(
        SubjectQuote $subject,
        Closure $proceed,
        Product $product,
        $buyRequest = null,
        $processMode = AbstractType::PROCESS_MODE_FULL
    )
    {
        if (!$buyRequest instanceof DataObject)
            return $proceed($product, $buyRequest, $processMode);

        $currentOrder = $this->registry->registry('current_order');
        (!empty($currentOrder)) ? $isReordered = true : $isReordered = false;

        $this->cartHelper->prepareDraft($isReordered, $isReordered);

        $result = $proceed($product, $buyRequest, $processMode);

        return is_string($result) ? $result : $this->setPrintformerData($result);
    }

    /**
     * Set printformer data for updated quote item
     * @param SubjectQuote $subject
     * @param $result
     * @return Item|string
     */
    public function afterUpdateItem(
        SubjectQuote $subject,
        $result
    )
    {
        return is_string($result) ? $result : $this->setPrintformerData($result);
    }

    /**
     * Load printformer data to quote-item
     * @param Item $item
     * @return Item
     */
    protected function setPrintformerData(Item $item)
    {
        //load pf-date for main- or parent-product item
        $this->loadPrintformerDataByQuoteItem($item);

        //load pf-data for child-product-item
        if ($this->configHelper->useChildProduct($item->getProductType())) {
            $childItems = $item->getChildren();
            if (!empty($childItems)) {
                $firstChildItem = $childItems[0];
                $this->loadPrintformerDataByQuoteItem($firstChildItem);
            }
        }

        return $item;
    }

    /**
     * Set draft ids from buy-request to quote-item
     * @param Item\AbstractItem $quoteItem
     * @return void
     */
    private function loadPrintformerDataByQuoteItem(SubjectQuote\Item\AbstractItem $quoteItem)
    {
        try {
            $buyRequest = $quoteItem->getBuyRequest();
            if (!empty($buyRequest)) {
                if (isset($buyRequest[InstallSchema::COLUMN_NAME_DRAFTID])) {
                    $storeId = $this->storeManager->getStore()->getId();
                    $buyRequest = $quoteItem->getBuyRequest();
                    $draftHashRelations = $buyRequest->getData('draft_hash_relations');
                    if (isset($draftHashRelations) && is_array($draftHashRelations)) {
                        if (isset($draftHashRelations[$quoteItem->getProduct()->getId()])) {
                            $newDraftHashes = $draftHashRelations[$quoteItem->getProduct()->getId()];
                            $newDraftHashField = implode(',', $newDraftHashes);
                            $quoteItem->setData(InstallSchema::COLUMN_NAME_DRAFTID, $newDraftHashField);
                            $quoteItem->setData(InstallSchema::COLUMN_NAME_STOREID, $storeId);
                            $this->session->unsetDraftIds($quoteItem->getProduct()->getId(), $storeId);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $this->logger->critical($e);
        }
    }

}
