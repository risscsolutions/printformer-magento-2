<?php

namespace Rissc\Printformer\Plugin\Quote;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\Quote as SubjectQuote;
use Rissc\Printformer\Helper\Session;
use Rissc\Printformer\Model\Draft;
use Rissc\Printformer\Setup\InstallSchema;
use Psr\Log\LoggerInterface;
use Rissc\Printformer\Helper\Api as ApiHelper;

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

    /**
     * QuoteModel constructor.
     * @param StoreManagerInterface $storeManager
     * @param Session $session
     * @param LoggerInterface $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Session $session,
        LoggerInterface $logger,
        ApiHelper $apiHelper
    ) {
        $this->storeManager = $storeManager;
        $this->session = $session;
        $this->logger = $logger;
        $this->_apiHelper = $apiHelper;
    }

    /**
     * Set printformer data for new quote item
     *
     * @param SubjectQuote $subject
     * @param $result
     * @return \Magento\Quote\Model\Quote\Item|string
     */
    public function aroundAddProduct(
        SubjectQuote $subject,
        \Closure $proceed,
        \Magento\Catalog\Model\Product $product,
        $buyRequest = null,
        $processMode = \Magento\Catalog\Model\Product\Type\AbstractType::PROCESS_MODE_FULL
    )
    {
        $draftIds = $buyRequest->getData(InstallSchema::COLUMN_NAME_DRAFTID);
        if (!empty($draftIds)) {
            $draftHashArray = explode(',', $draftIds);

            $draftHashRelations = [];
            foreach ($draftHashArray as $draftId) {
                if ($draftId == '') {
                    continue;
                }
                /** @var Draft $draftProcess */
                $draftProcess = $this->_apiHelper->draftProcess($draftId);
                if ($draftProcess->getId()) {
                    $draftHashRelations[$draftProcess->getPrintformerProductId()] = $draftProcess->getDraftId();
                }
            }

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
     * @return \Magento\Quote\Model\Quote\Item|string
     */
    public function afterUpdateItem(SubjectQuote $subject, $result)
    {
        return is_string($result) ? $result : $this->setPrintformerData($result);
    }

    /**
     * Set printformer data
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return \Magento\Quote\Model\Quote\Item
     */
    protected function setPrintformerData(\Magento\Quote\Model\Quote\Item $item)
    {
        try {
            if (isset($item->getBuyRequest()[InstallSchema::COLUMN_NAME_DRAFTID])) {
                $storeId = $this->storeManager->getStore()->getId();
                $buyRequest = $item->getBuyRequest();
                $draftIds = $buyRequest->getData(InstallSchema::COLUMN_NAME_DRAFTID);
                $draftHashArray = explode(',', $draftIds);

                foreach($draftHashArray as $draftId) {
                    $item->setData(InstallSchema::COLUMN_NAME_STOREID, $storeId);
                    $item->setData(InstallSchema::COLUMN_NAME_DRAFTID, $draftId);
                }
                
                $this->session->unsDraftId($item->getProduct()->getId(), $storeId);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return $item;
    }
}
