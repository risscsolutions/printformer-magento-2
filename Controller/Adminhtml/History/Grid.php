<?php
namespace Rissc\Printformer\Controller\Adminhtml\History;

use Rissc\Printformer\Controller\Adminhtml\AbstractController;

/**
 * Class Grid
 * @package Rissc\Printformer\Controller\Adminhtml\History
 */
class Grid
    extends AbstractController
{
    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        return $this->_resultPageFactory->create();
    }
}