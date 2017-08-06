<?php

namespace Rissc\Printformer\Block\Adminhtml\Drafts\Grid\Renderer;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\DataObject;
use Magento\Sales\Model\Order\ItemFactory;
use Magento\Sales\Model\Order\Item as OrderItem;
use Rissc\Printformer\Model\Draft;

class Order extends AbstractRenderer
{
    /**
     * @var ItemFactory
     */
    protected $_itemFactory;

    /**
     * @var UrlInterface
     */
    protected $_url;

    /**
     * Order constructor.
     * @param Context $context
     * @param ItemFactory $itemFactory
     * @param UrlInterface $url
     * @param array $data
     */
    public function __construct(
        Context $context,
        ItemFactory $itemFactory,
        UrlInterface $url,
        array $data = []
    ) {
        $this->_itemFactory = $itemFactory;
        $this->_url = $url;

        parent::__construct($context, $data);
    }

    public function render(DataObject $row)
    {
        /** @var Draft $row */
        if($orderItemId = $row->getOrderItemId()) {
            /** @var OrderItem $orderItem */
            $orderItem = $this->_itemFactory->create();
            $orderItem->getResource()->load($orderItem, $orderItemId);

            if($orderItem->getId() && $orderItem->getId() == $orderItemId) {
                $orderUrl = $this->_url->getUrl('sales/order/view', ['order_id' => $orderItem->getOrderId()]);
                return '<a href="' . $orderUrl . '">#' . $orderItem->getOrder()->getIncrementId() . '</a>';
            }
        }

        return __('Not ordered yet!');
    }
}