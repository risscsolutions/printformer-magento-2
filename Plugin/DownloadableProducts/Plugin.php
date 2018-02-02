<?php

namespace Rissc\Printformer\Plugin\DownloadableProducts;

use Magento\Downloadable\Block\Customer\Products\ListProducts;
use Magento\Downloadable\Model\Link\Purchased\Item;
use Magento\Sales\Model\Order\Item as OrderItem;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Rissc\Printformer\Helper\Session as SessionHelper;

class Plugin
{

    protected $_orderCollectionFactory;

    protected $_customer;

    public function __construct(
        SessionHelper $session,
        OrderCollectionFactory $orderCollectionFactory
    )
    {
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_customer = $session->getCustomerSession()->getCustomer();
    }

    public function aroundGetDownloadUrl(ListProducts $subject, \Closure $getDownloadUrl, Item $item)
    {
        $url = $getDownloadUrl($item);
        $orderCollection = $this->_orderCollectionFactory->create();
        $userOrderCollection = $orderCollection->addAttributeToFilter('customer_id', ['eq' => $this->_customer->getId()]);
        foreach ($userOrderCollection as $order) {
            /** @var OrderItem $orderItem */
            foreach ($order->getAllItems() as $orderItem) {
                if ($orderItem->getId() == $item->getOrderItemId()) {
                    if ($orderItem->getPrintformerDraftid() != null) {
                        $url = $subject->getUrl('printformer/get/pdf', ['draft_id' => $orderItem->getPrintformerDraftid(),
                            'quote_id' => $order->getQuoteId()]);
                    }
                }
            }
        }
        return $url;
    }

}