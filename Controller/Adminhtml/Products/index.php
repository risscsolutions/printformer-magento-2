<?php

namespace Rissc\Printformer\Controller\Adminhtml\Products;

use Rissc\Printformer\Controller\Adminhtml\AbstractController;

class Index extends AbstractController
{
    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->_forward('grid');
            return null;
        }

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu('Rissc_Printformer::main_menu');
        $resultPage->getConfig()->getTitle()->prepend(__('Printformer Products'));

        return $resultPage;
    }
}