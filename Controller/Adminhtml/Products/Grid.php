<?php

namespace Rissc\Printformer\Controller\Adminhtml\Products;

use Rissc\Printformer\Controller\Adminhtml\AbstractController;

class Grid extends AbstractController
{
    public function execute()
    {
        return $this->_resultPageFactory->create();
    }
}