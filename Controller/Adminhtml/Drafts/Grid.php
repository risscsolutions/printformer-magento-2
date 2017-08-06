<?php

namespace Rissc\Printformer\Controller\Adminhtml\Drafts;

use Rissc\Printformer\Controller\Adminhtml\AbstractController;

class Grid extends AbstractController
{
    public function execute()
    {
        return $this->_resultPageFactory->create();
    }
}