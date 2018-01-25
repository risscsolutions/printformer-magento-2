<?php

namespace Rissc\Printformer\Block\Adminhtml\Drafts\Grid\Renderer;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Sales\Model\Order\ItemFactory;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Backend\Model\UrlInterface;
use Rissc\Printformer\Model\Draft;
use Rissc\Printformer\Helper\Api as ApiHelper;
use Rissc\Printformer\Helper\Config;

class DraftEditor extends AbstractRenderer
{
    /**
     * @var ApiHelper
     */
    protected $_apiHelper;

    /**
     * @var ItemFactory
     */
    protected $_itemFactory;

    /**
     * @var UrlInterface
     */
    protected $_url;

    /** @var Config */
    protected $_config;

    /**
     * DraftEditor constructor.
     * @param Context $context
     * @param ApiHelper $apiHelper
     * @param ItemFactory $itemFactory
     * @param UrlInterface $url
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        ApiHelper $apiHelper,
        ItemFactory $itemFactory,
        UrlInterface $url,
        Config $config,
        array $data = []
    ) {
        $this->_apiHelper = $apiHelper;
        $this->_itemFactory = $itemFactory;
        $this->_url = $url;
        $this->_config = $config;

        parent::__construct($context, $data);
    }

    /**
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        /** @var Draft $row */
        $html = '';
        if($draftId = $row->getDraftId()) {
            $html .= '<div><span>' . __('Open Editor') . ':</span><br />';
            $html .= '<a href="' . $this->getEditorUrl($row) . '" target="_blank">';
            $html .= $row->getDraftId();
            $html .= '</a></div>';
        }

        return $html;
    }

    /**
     * @param DataObject $row
     * @return string
     */
    protected function getEditorUrl(\Magento\Framework\DataObject $row)
    {
        /** @var Draft $row */
        $referrerUrl = null;
        if($orderItemId = $row->getOrderItemId()) {
            /** @var OrderItem $orderItem */
            $orderItem = $this->_itemFactory->create();
            $orderItem->getResource()->load($orderItem, $orderItemId);

            if($orderItem->getId() && $orderItem->getId() == $orderItemId) {
                $referrerUrl = $this->_url->getUrl('sales/order/view', ['order_id' => $orderItem->getOrderId()]);
            }
        }

        $draftProcess = $this->_apiHelper->draftProcess($row->getDraftId());

        $editorParams = [
            'product_id' => $draftProcess->getProductId(),
            'data' => [
                'draft_process' => $draftProcess->getId(),
                'callback_url' => $referrerUrl
            ]
        ];
        if(!$referrerUrl) {
            $referrerUrl = $this->_url->getUrl('printformer/drafts/index');
        }
        if ($this->_config->isV2Enabled()) {
            return $this->_apiHelper->getEditorWebtokenUrl($row->getDraftId(), $draftProcess->getUserIdentifier(), $editorParams);
        } else {
            return $this->_apiHelper->apiUrl()->setStoreId($row->getStoreid())
                ->getAdminEditor($row->getDraftId(), $editorParams, $referrerUrl);
        }
    }
}