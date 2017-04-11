<?php
namespace Rissc\Printformer\Cron;

class DraftCleanup
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Rissc\Printformer\Model\DraftFactory
     */
    protected $draftFactory;

    /**
     * @var \Rissc\Printformer\Helper\Config
     */
    protected $configHelper;

    /**
     * @var \Rissc\Printformer\Gateway\User\Draft
     */
    protected $draftGateway;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Rissc\Printformer\Model\DraftFactory $draftFactory
     * @param \Rissc\Printformer\Helper\Config $configHelper
     * @param \Rissc\Printformer\Gateway\User\Draft $draftGateway
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Rissc\Printformer\Model\DraftFactory $draftFactory,
        \Rissc\Printformer\Helper\Config $configHelper,
        \Rissc\Printformer\Gateway\User\Draft $draftGateway
    ) {
        $this->logger = $logger;
        $this->draftFactory = $draftFactory;
        $this->configHelper = $configHelper;
        $this->draftGateway = $draftGateway;
    }

    /**
     * @return void
     */
    public function execute()
    {
        if (!$this->configHelper->isCronEnabled()) {
            return;
        }

        $drafts = $this->draftFactory->create()->getCollection()
            ->addFieldToFilter('order_item_id', ['null' => true])
            ->addFieldToFilter('created_at', ['lt' => $this->_getCleanupTimestamp()]);

        foreach ($drafts as $draft) {
            try {
                $this->draftGateway
                    ->deleteDraft($draft->getDraftId(), $draft->getStoreId());
                $draft->delete();
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
    }

    /**
     * @return integer
     */
    protected function _getCleanupTimestamp()
    {
        $interval = sprintf("P%dD", (int) $this->configHelper->getCronCleanupDays());
        return (new \DateTime())->sub(new \DateInterval($interval))
            ->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
    }
}
