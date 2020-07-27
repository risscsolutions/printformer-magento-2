<?php

namespace Rissc\Printformer\Plugin\Sales;

use Magento\Sales\Model\Order as OrderModel;
use Magento\Sales\Model\ResourceModel\Order;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Psr\Log\LoggerInterface;
use Rissc\Printformer\Gateway\Admin\Draft;
use Rissc\Printformer\Helper\Api;
use Rissc\Printformer\Helper\Config;
use Rissc\Printformer\Model\DraftFactory;
use Rissc\Printformer\Setup\InstallSchema;

class OrderResourceModel
{
    protected $api;
    protected $config;
    protected $draft;
    protected $draftFactory;
    protected $logger;

    public function __construct(
        Api $api,
        Config $config,
        Draft $draft,
        DraftFactory $draftFactory,
        LoggerInterface $logger
    ) {
        $this->api = $api;
        $this->config = $config;
        $this->draft = $draft;
        $this->draftFactory = $draftFactory;
        $this->logger = $logger;
    }

    public function afterSave(Order $orderResourceModel, AbstractDb $result, OrderModel $orderModel) : void
    {
        try {
            $draftIds = [];
            foreach ($orderModel->getAllItems() as $item) {
                if ($item->getPrintformerOrdered() || !$item->getPrintformerDraftid()) {
                    continue;
                }

                $itemDraftIds = explode(',', $item->getData(InstallSchema::COLUMN_NAME_DRAFTID));
                foreach ($itemDraftIds as $draftId) {
                    $this->draftFactory
                        ->create()
                        ->load($draftId, 'draft_id')
                        ->setOrderItemId($item->getId())
                        ->save();

                    $draftIds[] = $draftId;
                }
            }

            if (empty($draftIds)) {
                return;
            } else {
                $draftIds = array_unique($draftIds);
            }

            if (in_array($orderModel->getStatus(), $this->config->getOrderStatus())) {
                if ($this->config->getProcessingType() == Draft::DRAFT_PROCESSING_TYPE_SYNC && !$this->config->isV2Enabled()) {
                    $this->draft->setDraftOrdered($orderModel);
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return;
    }
}