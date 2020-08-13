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
use Rissc\Printformer\Cron\Processing\Runs\External;

class OrderResourceModel
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Draft
     */
    protected $draft;

    /**
     * @var DraftFactory
     */
    protected $draftFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var External
     */
    private $external;

    /**
     * OrderResourceModel constructor.
     * @param Api $api
     * @param Config $config
     * @param Draft $draft
     * @param DraftFactory $draftFactory
     * @param LoggerInterface $logger
     * @param External $external
     */
    public function __construct(
        Api $api,
        Config $config,
        Draft $draft,
        DraftFactory $draftFactory,
        LoggerInterface $logger,
        External $external
    ) {
        $this->api = $api;
        $this->config = $config;
        $this->draft = $draft;
        $this->draftFactory = $draftFactory;
        $this->logger = $logger;
        $this->external = $external;
    }

    public function afterSave(Order $orderResourceModel, AbstractDb $result, OrderModel $orderModel) : void
    {
        try {
            $draftIds = [];
            $orderItems = [];
            foreach ($orderModel->getAllItems() as $item) {
                if($item->getProductType() == 'downloadable'){
                    array_push($orderItems, $item->getId());
                }

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

            //todo: check if this setting is here required: (important for other shops)
            //$this->config->getProcessingType() == Draft::DRAFT_PROCESSING_TYPE_SYNC && !$this->config->isV2Enabled()
            //todo: other question, is it needed to check this option then also to install this specific crons or not?
            if (!empty($orderItems)){
                $orderItemIds = implode(',', $orderItems);
                $this->external->setOrderItemIdsToFilter($orderItemIds);
                $this->external->execute();
            }

            if (empty($draftIds)) {
                return;
            } else {
                $draftIds = array_unique($draftIds);
            }

            if (in_array($orderModel->getStatus(), $this->config->getOrderStatus())) {
                if ($this->config->getProcessingType() == Draft::DRAFT_PROCESSING_TYPE_SYNC && !$this->config->isV2Enabled()) {
                    $this->draft->setDraftOrdered($orderModel);
                } else {
                    $this->api->setAsyncOrdered($draftIds);
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return;
    }
}