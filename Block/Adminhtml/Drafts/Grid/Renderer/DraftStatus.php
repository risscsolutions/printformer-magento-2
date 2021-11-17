<?php

namespace Rissc\Printformer\Block\Adminhtml\Drafts\Grid\Renderer;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Rissc\Printformer\Model\Draft;
use Magento\Sales\Model\Order\ItemFactory;
use Magento\Backend\Model\UrlInterface;
use Rissc\Printformer\Setup\InstallData;
use Rissc\Printformer\Helper\Api;

class DraftStatus extends AbstractRenderer
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
     * DraftStatus constructor.
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
        $itemOrdered = false;
        if($orderItemId = $row->getOrderItemId()) {
            $item = $this->_itemFactory->create();
            $item->getResource()->load($item, $orderItemId);
            if($item->getId()) {
                $itemOrdered = (bool)$item->getPrintformerOrdered();
                if ($row->getProcessingStatus() == 1 && !$itemOrdered) {
                    $item->setPrintformerOrdered(1);
                    $item->getResource()->save($item);

                    $itemOrdered = (bool)$item->getPrintformerOrdered();
                }
            }
        }

        $resultHtml = '';

        if($row->getProcessingId()) {
            $resultHtml .= '<br /><small style="color: #C0C0C0">' . __('Processing ID') . ': <strong>' . $row->getProcessingId() . '</strong></small>';
        }

        if (isset($item)){
            $printformerCountState = (integer)$item->getPrintformerCountState();
            if($printformerCountState === Api::ProcessingStateAfterOrder){
                $printformerCountStateMessage = 'Processed after order';
            } elseif ($printformerCountState === Api::ProcessingStateAdminMassResend){
                $printformerCountStateMessage = 'Processed after mass-resend';
            } elseif ($printformerCountState === Api::ProcessingStateAfterCron){
                $printformerCountStateMessage = 'Processed after cron';
            } elseif ($printformerCountState === Api::ProcessingStateAfterUploadCallback){
                $printformerCountStateMessage = 'processed after upload-callback';
            }

            if ($printformerCountState !== 0 && isset($printformerCountStateMessage) && $printformerCountState != '0'){
                if(!$row->getProcessingId()) {
                    $resultHtml .= '<br /><small style="color: #C0C0C0">' . __('Processing failed') . '... <strong>' . $row->getProcessingId() . '</strong></small>';
                }
                $resultHtml .= '<br /><small style="color: #C0C0C0">' . __('Last execution') . ': <strong>' . $item->getPrintformerCountDate() . '</strong></small>';
                $resultHtml .= '<br /><small style="color: #C0C0C0">' . __('Last location') . ': <strong>' . $printformerCountStateMessage . '</strong></small>';
            }
        }

        $draftStatusImage = $this->getViewFileUrl(InstallData::MODULE_NAMESPACE . '_' . InstallData::MODULE_NAME . '/images/proccessing_status_red.svg');
        $draftStatus = __('Not processed yet!');
        if($itemOrdered && $row->getProcessingId()) {
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
        ' . $resultHtml;
    }
}