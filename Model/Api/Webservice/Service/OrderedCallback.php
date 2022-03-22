<?php

namespace Rissc\Printformer\Model\Api\Webservice\Service;

use Magento\Framework\DataObject;
use Magento\Framework\Webapi\Rest\Request;
use Rissc\Printformer\Model\Api\Webservice\AbstractService;
use Rissc\Printformer\Model\Api\Webservice\Data\OrderedCallbackInterface;
use Rissc\Printformer\Model\DraftFactory;
use Rissc\Printformer\Model\Draft;
use Rissc\Printformer\Model\ResourceModel\Draft\Collection as DraftCollection;
use Magento\Framework\Event\ManagerInterface;
use Rissc\Printformer\Helper\Log as LogHelper;

/**
 * Class OrderedCallback
 * @package Rissc\Printformer\Model\Api\Webservice\Service
 */
class OrderedCallback
    extends AbstractService
    implements OrderedCallbackInterface
{
    /** @var DraftFactory */
    protected $_draftFactory;

    /** @var ManagerInterface */
    protected $_eventManager;

    /** @var LogHelper */
    protected $_logHelper;

    public function __construct(
        Request $_request,
        DraftFactory $_draftFactory,
        ManagerInterface $_eventManager,
        LogHelper $logHelper
    )
    {
        $this->_draftFactory = $_draftFactory;
        $this->_eventManager = $_eventManager;
        $this->_logHelper = $logHelper;

        parent::__construct($_request);
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function execute()
    {
        $postParams = $this->getRequest()->getBodyParams();

        $_historyData = [
            'api_url' => $this->_request->getUri()->toString(),
            'request_data' => json_encode($postParams),
            'direction' => 'incoming'
        ];
        $existingLogEntry = null;
        $_apiResponseObject = new DataObject();
        if (
            isset($postParams['processingId']) &&
            isset($postParams['draftStates']) &&
            is_array($postParams['draftStates'])
        ) {
            $_draftIdArray = [];
            $_draftStatus = [];
            foreach ($postParams['draftStates'] as $_draftState) {
                if (isset($_draftState['draftId'])) {
                    $_draftIdArray[] = $_draftState['draftId'];
                    $_draftStatus[$_draftState['draftId']] = $_draftState['state'];
                }
            }

            $_historyData['draft_id'] = implode(', ', $_draftIdArray);
            $existingLogEntry = $this->_logHelper->getEntry(
                [
                    'filter_array' => [
                        [
                            'field' => 'draft_id',
                            'condition' => ['eq' => $_historyData['draft_id']]
                        ]
                    ]
                ]
            );

            $_draftCounter = count($postParams['draftStates']);
            /** @var Draft $_draft */
            $_draft = $this->_draftFactory->create();
            /** @var DraftCollection $_draftCollection */
            $_draftCollection = $_draft->getCollection();

            $_draftCollection->addFieldToFilter('draft_id', ['in' => $_draftIdArray])
                ->addFieldToFilter('processing_id', ['eq' => $postParams['processingId']]);

            /** @var Draft $_draft */
            $_successedDraftId = 0;
            $_drafts = [];
            foreach ($_draftCollection->getItems() as $_draft) {
                if (isset($_draftStatus[$_draft->getDraftId()])) {
                    $status = $this->_getProcessingStatus($_draftStatus[$_draft->getDraftId()]);
                    if ($status == 1) {
                        $_successedDraftId++;
                    }
                    $_draft->setProcessingStatus($status);
                    $_draft->getResource()->save($_draft);
                    $_drafts[] = $_draft;
                }
            }

            if ($_draftCounter === $_successedDraftId) {
                if (is_array($_drafts) && !empty($_drafts)) {
                    $_historyData['status'] = 'all-ok';
                    $this->_eventManager->dispatch(
                        'printformer_draft_processed_success',
                        [
                            'printformer_drafts' => $_drafts,
                            'api_response_object' => $_apiResponseObject
                        ]
                    );
                } else {
                    $_historyData['status'] = 'wrong-data';
                }
            } else {
                $_historyData['status'] = 'processing-count-wrong';
            }
        } else {
            $_historyData['status'] = 'wrong-response-data';
        }
        $_historyData['response_data'] = json_encode($_apiResponseObject->getMessage());
        if (!$existingLogEntry) {
            $this->_logHelper->createEntry($_historyData);
        } else {
            $this->_logHelper->editEntry($existingLogEntry->getId(), $_historyData);
        }

        return json_encode($_apiResponseObject->getMessage());
    }

    /**
     * @param string $stringStatus
     *
     * @return int
     */
    protected function _getProcessingStatus($stringStatus)
    {
        switch ($stringStatus) {
            case 'processed':
                return 1;
                break;
            case 'failed':
                return 0;
                break;
            case 'pending':
            case 'in-process':
            default:
                return 2;
                break;
        }
    }
}