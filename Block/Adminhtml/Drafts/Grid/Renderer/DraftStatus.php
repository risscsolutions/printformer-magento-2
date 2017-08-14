<?php

namespace Rissc\Printformer\Block\Adminhtml\Drafts\Grid\Renderer;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Rissc\Printformer\Model\Draft;
use Rissc\Printformer\Helper\Url as UrlHelper;
use Magento\Sales\Model\Order\ItemFactory;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Backend\Model\UrlInterface;
use \DateTime;
use Rissc\Printformer\Setup\InstallData;

class DraftStatus extends AbstractRenderer
{
    /**
     * @var UrlHelper
     */
    protected $_urlHelper;

    /**
     * @var ItemFactory
     */
    protected $_itemFactory;

    /**
     * @var UrlInterface
     */
    protected $_url;

    /**
     * DraftStatus constructor.
     * @param Context $context
     * @param UrlHelper $urlHelper
     * @param ItemFactory $itemFactory
     * @param UrlInterface $url
     * @param array $data
     */
    public function __construct(
        Context $context,
        UrlHelper $urlHelper,
        ItemFactory $itemFactory,
        UrlInterface $url,
        array $data = []
    ) {
        $this->_urlHelper = $urlHelper;
        $this->_itemFactory = $itemFactory;
        $this->_url = $url;

        parent::__construct($context, $data);
    }

    public function render(DataObject $row)
    {
        /** @var Draft $row */
        $itemOrdered = false;
        if($orderItemId = $row->getOrderItemId()) {
            $item = $this->_itemFactory->create();
            $item->getResource()->load($item, $orderItemId);
            if($item->getId()) {
                $itemOrdered = (bool)$item->getPrintformerOrdered();
            }
        }

        $processingIdHtml = '';
        if($row->getProcessingId()) {
            $processingIdHtml .= '<br /><small style="color: #C0C0C0">' . __('Processing ID') . ': <strong>' . $row->getProcessingId() . '</strong></small>';
        }

        $draftStatusImage = $this->getViewFileUrl(InstallData::MODULE_NAMESPACE . '_' . InstallData::MODULE_NAME . '/images/proccessing_status_red.svg');
        $draftStatus = __('Not processed yet!');
        if($itemOrdered) {
            $draftStatusImage = $this->getViewFileUrl(InstallData::MODULE_NAMESPACE . '_' . InstallData::MODULE_NAME . '/images/proccessing_status_green.svg');
            $draftStatus = __('Processed successfully!');
        }
        return '
            <div>
                <img src="' . $draftStatusImage . '"
                     style="width:20px;height:20px;float:left;"
                     alt="' . $draftStatus . '"
                />
                <span style="float:left;margin-left:5px;display:block;line-height:19px;"><strong>' . $draftStatus  . '</strong></span>
                <div style="clear:both;"><!-- --></div>
            </div>
        ' . $processingIdHtml;
    }
}