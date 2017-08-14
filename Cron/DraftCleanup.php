<?php

namespace Rissc\Printformer\Cron;

use Psr\Log\LoggerInterface;
use Rissc\Printformer\Model\DraftFactory;
use Rissc\Printformer\Helper\Config;
use Rissc\Printformer\Gateway\User\Draft;

class DraftCleanup
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var DraftFactory
     */
    protected $_draftFactory;

    /**
     * @var Config
     */
    protected $_configHelper;

    /**
     * @var Draft
     */
    protected $_draftGateway;

    /**
     * DraftCleanup constructor.
     * @param LoggerInterface $logger
     * @param DraftFactory $draftFactory
     * @param Config $configHelper
     * @param Draft $draftGateway
     */
    public function __construct(
        LoggerInterface $logger,
        DraftFactory $draftFactory,
        Config $configHelper,
        Draft $draftGateway
    ) {
        $this->_logger = $logger;
        $this->_draftFactory = $draftFactory;
        $this->_configHelper = $configHelper;
        $this->_draftGateway = $draftGateway;
    }

    public function execute()
    {
        if (!$this->_configHelper->isCronEnabled()) {
            return;
        }

        $drafts = $this->_draftFactory->create()->getCollection()
            ->addFieldToFilter('order_item_id', ['null' => true])
            ->addFieldToFilter('created_at', ['lt' => $this->_getCleanupTimestamp()]);

        foreach ($drafts as $draft) {
            try {
                $this->_draftGateway
                    ->deleteDraft($draft->getDraftId(), $draft->getStoreId());
                $draft->delete();
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }
        }
    }

    /**
     * @return int
     */
    protected function _getCleanupTimestamp()
    {
        $interval = sprintf("P%dD", (int) $this->_configHelper->getCronCleanupDays());
        return (new \DateTime())->sub(new \DateInterval($interval))
            ->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
    }
}
