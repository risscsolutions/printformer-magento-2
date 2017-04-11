<?php

namespace Rissc\Printformer\Block\Adminhtml\History\Grid\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Backend\Block\Context;
use Rissc\Printformer\Setup\InstallData;

/**
 * Class Direction
 * @package Rissc\Printformer\Block\Adminhtml\History\Grid\Renderer
 */
class Direction
    extends AbstractRenderer
{
    /**
     * @param \Magento\Framework\DataObject $row
     *
     * @return string
     */
    public function render(DataObject $row)
    {
        /** @var \Rissc\Printformer\Model\History\Log $row */

        $returnImage = '';
        switch($row->getDirection())
        {
            case 'incoming':
                $returnImage = '<img title="Incoming Request" src="' . $this->getViewFileUrl(InstallData::MODULE_NAMESPACE . '_' . InstallData::MODULE_NAME . '/images/incoming.svg') . '" style="width:20px;height:20px;" alt="incoming" />';
                break;
            case 'outgoing':
                $returnImage = '<img title="Outgoing Request" src="' . $this->getViewFileUrl(InstallData::MODULE_NAMESPACE . '_' . InstallData::MODULE_NAME . '/images/outgoing.svg') . '" style="width:20px;height:20px;" alt="outgoing" />';
                break;
        }

        return $returnImage;
    }
}