<?php

namespace Rissc\Printformer\Plugin\Quote;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\Quote as SubjectQuote;
use Rissc\Printformer\Helper\Session;
use Rissc\Printformer\Setup\InstallSchema;
use Psr\Log\LoggerInterface;

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
     * QuoteModel constructor.
     * @param StoreManagerInterface $storeManager
     * @param Session $session
     * @param LoggerInterface $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Session $session,
        LoggerInterface $logger
    ) {
        $this->storeManager = $storeManager;
        $this->session = $session;
        $this->logger = $logger;
    }

    /**
     * Set printformer data for new quote item
     *
     * @param SubjectQuote $subject
     * @param $result
     * @return \Magento\Quote\Model\Quote\Item|string
     */
    public function afterAddProduct(SubjectQuote $subject, $result)
    {
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
                $draftId = $item->getBuyRequest()[InstallSchema::COLUMN_NAME_DRAFTID];

                $item->setData(InstallSchema::COLUMN_NAME_STOREID, $storeId);
                $item->setData(InstallSchema::COLUMN_NAME_DRAFTID, $draftId);

                $this->session->unsDraftId($item->getProduct()->getId(), $storeId);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return $item;
    }
}
