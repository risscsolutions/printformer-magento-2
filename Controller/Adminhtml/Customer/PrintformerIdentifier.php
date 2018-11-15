<?php
namespace Rissc\Printformer\Controller\Adminhtml\Customer;

use Magento\Customer\Controller\Adminhtml\Index as CustomerIndex;
use Magento\Framework\App\ResponseInterface;

class PrintformerIdentifier extends CustomerIndex
{

    /**
     * Execute action based on request and return result
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     */
    public function execute()
    {
        $this->initCurrentCustomer();
        $resultLayout = $this->resultLayoutFactory->create();
        return $resultLayout;
    }
}