<?php
namespace Rissc\Printformer\Controller\Adminhtml\Editor;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order\Item as OrderItem;
use Rissc\Printformer\Helper\Api as ApiHelper;
use Magento\Sales\Model\Order\ItemFactory;
use Rissc\Printformer\Model\Draft;

/**
 * To create jwt token for specific draft with store and order-item-id
 *
 * Class Webtoken
 * @package Rissc\Printformer\Controller\Adminhtml\Editor
 */
class Webtoken extends Action
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * @var ItemFactory
     */
    private $itemFactory;

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param ApiHelper $apiHelper
     * @param ItemFactory $itemFactory
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        ApiHelper $apiHelper,
        ItemFactory $itemFactory
    )
    {
        parent::__construct($context);
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->itemFactory = $itemFactory;
    }


    public function execute()
    {
        $this->logger->debug('draft-process callback started');

        $rowDraftHash = $this->getRequest()->getParam('draft_id');
        $rowOrderItemId = $this->getRequest()->getParam('order_item_Id');
        $rowStoreId = $this->getRequest()->getParam('store_id');

        /** @var Draft $row */
        $referrerUrl = null;
        $storeId = $rowStoreId;
        $this->apiHelper->setStoreId($storeId);

        if($orderItemId = $rowOrderItemId) {
        /** @var OrderItem $orderItem */
            $orderItem = $this->itemFactory->create();
            $orderItem->getResource()->load($orderItem, $orderItemId);

            if($orderItem->getId() && $orderItem->getId() == $orderItemId) {
                $referrerUrl = $this->_url->getUrl('sales/order/view', ['order_id' => $orderItem->getOrderId()]);
            }
        }

        $draftProcess = $this->apiHelper->draftProcess($rowDraftHash);

        $editorParams = [
            'product_id' => $draftProcess->getProductId(),
            'data' => [
                'draft_process' => $draftProcess->getId(),
                'callback_url' => $referrerUrl
            ]
        ];

        $url = $this->apiHelper->getEditorWebtokenUrl($rowDraftHash, $draftProcess->getUserIdentifier(), $editorParams);

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($url);
        return $resultRedirect;
    }
}