<?php

namespace Rissc\Printformer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Rissc\Printformer\Setup\InstallSchema;

class SetDraftOrderItemId implements ObserverInterface
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
     * @param   \Psr\Log\LoggerInterface               $logger
     * @param   \Rissc\Printformer\Helper\Config       $config
     * @param   \Rissc\Printformer\Model\DraftFactory  $draftFactory
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Rissc\Printformer\Helper\Config $config,
        \Rissc\Printformer\Model\DraftFactory $draftFactory
    ) {
        $this->logger       = $logger;
        $this->config       = $config;
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
             * @var \Magento\Sales\Model\Order $order
             */
            $order = $observer->getData('order');
            foreach ($order->getAllItems() as $item) {
                $draftIdsField
                    = $item->getData(InstallSchema::COLUMN_NAME_DRAFTID);
                if (!empty($draftIdsField)) {
                    foreach (
                        explode(',',
                            $item->getData(InstallSchema::COLUMN_NAME_DRAFTID))
                        as $draftId
                    ) {
                        if (!empty($draftId)) {
                            $this->draftFactory
                                ->create()
                                ->load($draftId, 'draft_id')
                                ->setOrderItemId($item->getId())
                                ->save();
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
