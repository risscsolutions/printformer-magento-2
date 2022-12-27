<?php

namespace Rissc\Printformer\Model\Api\Webservice\Service;

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Webapi\Rest\Request;
use Rissc\Printformer\Helper\Log as LogHelper;
use Rissc\Printformer\Model\AclData;
use Rissc\Printformer\Model\Api\Webservice\AbstractService;
use Rissc\Printformer\Model\Api\Webservice\Data\AclInterface;
use Rissc\Printformer\Model\Api\Webservice\Service\AclData as AclDataResponse;

/**
 * Class Acl
 *
 * @package Rissc\Printformer\Model\Api\Webservice\Service
 * @api
 */
class Acl extends AbstractService implements AclInterface
{

    /** @var ManagerInterface */
    protected $_eventManager;

    /** @var LogHelper */
    protected $_logHelper;

    /** @var JsonFactory */
    protected $_resultJsonFactory;

    public function __construct(
        Request $_request,
        ManagerInterface $_eventManager,
        LogHelper $logHelper,
        JsonFactory $resultJsonFactory
    ) {
        $this->_eventManager      = $_eventManager;
        $this->_logHelper         = $logHelper;
        $this->_resultJsonFactory = $resultJsonFactory;

        parent::__construct($_request);
    }

    /**
     * @return array|false|string
     */
    public function execute()
    {
        $data          = $this->getRequest()->getBodyParams();
        $returnActions = [];

        if (isset($data['actions'])) {
            foreach ($data['actions'] as $key => $actionData) {
                $aclData = new AclData($actionData);
                $this->_eventManager->dispatch('printformer_acl_process',
                    ['acl_data' => $aclData]);
                $returnActions[] = $aclData;
            }
        }

        $dataObject = new AclDataResponse();
        $dataObject->setActions($returnActions);

        return $dataObject;
    }
}
