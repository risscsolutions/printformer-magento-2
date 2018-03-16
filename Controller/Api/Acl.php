<?php

namespace Rissc\Printformer\Controller\Api;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Rissc\Printformer\Model\AclData;
use Rissc\Printformer\Model\AclData\Collection;

class Acl extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * Acl constructor.
     * @param JsonFactory $resultJsonFactory
     * @param Context $context
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        Context $context
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        $data = $this->getPostBody();

        $aclDataCollection = new Collection();

        if(isset($data['actions'])) {
            foreach($data['actions'] as $actionData) {
                $aclData = new AclData($actionData);
                $this->_eventManager->dispatch('printformer_acl_process', ['acl_data' => $aclData]);
                $aclDataCollection->addItem($aclData);
            }
        }

        return $result->setData($aclDataCollection->toArray());
    }

    /**
     * @return array|mixed
     */
    protected function getPostBody()
    {
        $postBody = file_get_contents('php://input');
        $data = json_decode($postBody, true);
        if(json_last_error() != JSON_ERROR_NONE) {
            $data = [];
        }
        return $data;
    }
}