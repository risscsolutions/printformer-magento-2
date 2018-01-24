<?php

namespace Rissc\Printformer\Controller\Adminhtml\Drafts;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\ItemFactory as OrderItemFactory;
use Magento\Sales\Model\Order\Item as OrderItem;
use Rissc\Printformer\Controller\Adminhtml\AbstractController;
use Rissc\Printformer\Model\DraftFactory;
use Rissc\Printformer\Model\Draft;
use Rissc\Printformer\Gateway\Admin\Draft as GatewayDraft;
use Rissc\Printformer\Helper\Config;
use Rissc\Printformer\Helper\Api as ApiHelper;

class MassResend extends AbstractController
{
    /**
     * @var DraftFactory
     */
    protected $_draftFactory;

    /**
     * @var OrderItemFactory
     */
    protected $_orderItemFactory;

    /**
     * @var Config
     */
    protected $_config;

    /**
     * @var GatewayDraft
     */
    protected $_printformerDraft;

    /** @var ApiHelper */
    protected $_apiHelper;

    /**
     * MassResend constructor.
     * @param Context $context
     * @param PageFactory $_resultPageFactory
     * @param DraftFactory $draftFactory
     * @param OrderItemFactory $orderItemFactory
     * @param Config $config
     * @param GatewayDraft $printformerDraft
     * @param ApiHelper $apiHelper
     */
    public function __construct(
        Context $context,
        PageFactory $_resultPageFactory,
        DraftFactory $draftFactory,
        OrderItemFactory $orderItemFactory,
        Config $config,
        GatewayDraft $printformerDraft,
        ApiHelper $apiHelper
    ) {
        $this->_draftFactory = $draftFactory;
        $this->_orderItemFactory = $orderItemFactory;
        $this->_config = $config;
        $this->_printformerDraft = $printformerDraft;
        $this->_apiHelper = $apiHelper;

        parent::__construct($context, $_resultPageFactory);
    }

    public function execute()
    {
        $drafts = $this->getRequest()->getParam('drafts');
        if(!empty($drafts)) {
            /** @var Draft $draft */
            $draft = $this->_draftFactory->create();
            $draftCollection = $draft->getCollection()->addFieldToFilter('id', ['in' => $drafts]);

            foreach($draftCollection as $draft) {
                /** @var OrderItem $orderItem */
                $orderItem = $this->_orderItemFactory->create();
                $orderItem = $orderItem->getCollection()
                    ->addFieldToFilter('printformer_draftid', ['eq' => $draft->getDraftId()])
                    ->addFieldToFilter('printformer_storeid', ['eq' => $draft->getStoreId()])
                    ->load()
                    ->getFirstItem();

                if($orderItem->getId()) {
                    /** @var Order $order */
                    $order = $orderItem->getOrder();
                    $draftIds = [];
                    foreach ($order->getAllItems() as $item) {
                        if ($item->getPrintformerOrdered() || !$item->getPrintformerDraftid()) {
                            continue;
                        }
                        $draftIds[] = $item->getPrintformerDraftid();
                    }
                    if ($this->_config->getProcessingType() == GatewayDraft::DRAFT_PROCESSING_TYPE_SYNC &&
                        !$this->_config->isV2Enabled()) {
                        $this->_printformerDraft->setDraftOrdered($order);
                    } else {
                        $this->_apiHelper->setAsyncOrdered($draftIds);
                    }
                    $orderItem->setPrintformerOrdered(1);
                    $orderItem->getResource()->save($orderItem);
                }
            }
            $this->messageManager->addSuccessMessage(__('Drafts have been resend to processing.'));
            return $this->_redirect('*/*/index');
        }
        $this->messageManager->addSuccessMessage(__('No drafts have been processed.'));
        return $this->_redirect('*/*/index');
    }
}