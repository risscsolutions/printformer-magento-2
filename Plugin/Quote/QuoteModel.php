<?php

namespace Rissc\Printformer\Plugin\Quote;

use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\Quote as SubjectQuote;
use Rissc\Printformer\Helper\Session;
use Rissc\Printformer\Model\Draft;
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
    protected $_apiHelper;

    private Registry $registry;

    private CartHelper $cartHelper;
    private ConfigHelper $configHelper;

    /**
     * QuoteModel constructor.
     * @param StoreManagerInterface $storeManager
     * @param Session $session
     * @param LoggerInterface $logger
     * @param ApiHelper $apiHelper
     * @param Registry $registry
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Session $session,
        LoggerInterface $logger,
        ApiHelper $apiHelper,
        Registry $registry,
        CartHelper $cartHelper,
        ConfigHelper $configHelper
    ) {
        $this->storeManager = $storeManager;
        $this->session = $session;
        $this->logger = $logger;
        $this->_apiHelper = $apiHelper;
        $this->registry = $registry;
        $this->cartHelper = $cartHelper;
        $this->configHelper = $configHelper;
    }

    /**
     * @param SubjectQuote $subject
     * @param \Closure $proceed
     * @param Product $product
     * @param null|float|DataObject $buyRequest
     * @param $processMode
     * @return Item|mixed|string
     * @throws \Exception
     */
    public function aroundAddProduct(
        SubjectQuote $subject,
        \Closure $proceed,
        Product $product,
        $buyRequest = null,
        $processMode = \Magento\Catalog\Model\Product\Type\AbstractType::PROCESS_MODE_FULL
    ) {
        if(!$buyRequest instanceof DataObject)
            return $proceed($product, $buyRequest, $processMode);

        $currentOrder = $this->registry->registry('current_order');
        (!empty($currentOrder)) ? $isReordered = true : $isReordered = false;

        $draftIds = $buyRequest->getData(InstallSchema::COLUMN_NAME_DRAFTID);
        if (!empty($draftIds)) {
            $draftHashArray = explode(',', $draftIds);

            $draftHashRelations = [];
            $newDraftHashArray = [];
            foreach ($draftHashArray as $draftId) {
                if ($draftId == '') {
                    continue;
                }

                if ($isReordered) {
                    $oldDraftId = $draftId;
                    $customerId = $this->session->getCustomerSession()->getCustomerId();
                    $newDraftProcess = $this->_apiHelper->generateNewReplicateDraft($oldDraftId, $customerId);
                    if (!empty($newDraftProcess)) {
                        $newDraftId = $newDraftProcess->getDraftId();
                        if (!empty($newDraftId)) {
                            $draftId = $newDraftId;
                            $relations = $buyRequest->getData('draft_hash_relations');
                            if (!empty($relations[$newDraftProcess->getProductId()][$newDraftProcess->getPrintformerProductId()])) {
                                $relations[$newDraftProcess->getProductId()][$newDraftProcess->getPrintformerProductId()] = $newDraftId;
                                $buyRequest->setData('draft_hash_relations', $relations);
                            }
                        }
                    }
                }

                if (empty($newDraftProcess)) {
                    /** @var Draft $draftProcess */
                    $draftProcess = $this->_apiHelper->draftProcess($draftId);
                } else {
                    $draftProcess = $newDraftProcess;
                }

                if ($draftProcess->getId()) {
                    $draftHashRelations = $this->cartHelper->updateDraftHashRelations(
                        $draftHashRelations,
                        $draftProcess->getProductId(),
                        $draftProcess->getPrintformerProductId(),
                        $draftProcess->getDraftId()
                    );
                }

                array_push($newDraftHashArray, $draftId);
            }

            $newDraftHashArrayFormatted = implode(',', $newDraftHashArray);
            $buyRequest->setData(InstallSchema::COLUMN_NAME_DRAFTID, $newDraftHashArrayFormatted);

            if (!empty($draftHashRelations)) {
                $buyRequest->setData('draft_hash_relations', $draftHashRelations);
            }
        }

        $result = $proceed($product, $buyRequest, $processMode);

        return is_string($result) ? $result : $this->setPrintformerData($result);
    }

    /**
     * Set printformer data for updated quote item
     *
     * @param SubjectQuote $subject
     * @param $result
     * @return Item|string
     */
    public function afterUpdateItem(SubjectQuote $subject, $result)
    {
        return is_string($result) ? $result : $this->setPrintformerData($result);
    }

    /**
     * Load printformer data to quote-item
     *
     * @param Item $item
     * @return Item
     */
    protected function setPrintformerData(Item $item)
    {
        //load pf-date for main- or parent-product item
        $this->loadPrintformerDataByQuoteItem($item);

        //load pf-data for child-product-item
        if($this->configHelper->useChildProduct($item->getProductType())) {
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
     *
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
                    if (isset($draftHashRelations) && is_array($draftHashRelations)){
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
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

}
