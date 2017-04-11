<?php
namespace Rissc\Printformer\Block\Adminhtml\Drafts\Grid\Renderer;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Rissc\Printformer\Model\Draft;
use Rissc\Printformer\Helper\Url as UrlHelper;
use Magento\Sales\Model\Order\ItemFactory;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Backend\Model\UrlInterface;

class DraftEditor
    extends AbstractRenderer
{
    /** @var UrlHelper */
    protected $_urlHelper;

    /** @var ItemFactory */
    protected $_itemFactory;

    /** @var UrlInterface */
    protected $_url;

    public function __construct(
        Context $context,
        UrlHelper $urlHelper,
        ItemFactory $itemFactory,
        UrlInterface $url,
        array $data = []
    )
    {
        $this->_urlHelper = $urlHelper;
        $this->_itemFactory = $itemFactory;
        $this->_url = $url;

        parent::__construct($context, $data);
    }

    public function render(DataObject $row)
    {
        /** @var Draft $row */
        $html = '';
        if($draftId = $row->getDraftId())
        {
            $html .= '<div><span>' . __('Open Editor') . ':</span><br />';
            $html .= '<a href="' . $this->getEditorUrl($row) . '" target="_blank">';
            $html .= $row->getDraftId();
            $html .= '</a></div>';
        }

        return $html;
    }

    protected function getEditorUrl(\Magento\Framework\DataObject $row)
    {
        /** @var Draft $row */
        $referrerUrl = null;
        if($orderItemId = $row->getOrderItemId())
        {
            /** @var OrderItem $orderItem */
            $orderItem = $this->_itemFactory->create();
            $orderItem->getResource()->load($orderItem, $orderItemId);

            if($orderItem->getId() && $orderItem->getId() == $orderItemId)
            {
                $referrerUrl = $this->_url->getUrl('sales/order/view', ['order_id' => $orderItem->getOrderId()]);
            }
        }

        return $this->_urlHelper->setStoreId($row->getStoreid())
            ->getAdminEditorUrl($row->getDraftId(), null, $referrerUrl);
    }
}