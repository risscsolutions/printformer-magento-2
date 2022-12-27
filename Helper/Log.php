<?php

namespace Rissc\Printformer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Message\Manager as MessageManager;
use Rissc\Printformer\Model\History\Log as LogModel;
use Rissc\Printformer\Model\History\LogFactory;

class Log extends AbstractHelper
{
    /**
     * @var LogFactory
     */
    protected $_logFactory;

    /**
     * @var MessageManager
     */
    protected $_messageManager;

    /**
     * @var $apiUrl
     */
    private $apiUrl;

    /**
     * @var $requestType
     */
    private $requestType = 'get';

    /**
     * @var $requestData
     */
    private $requestData;

    /**
     * @var $direction
     */
    private $direction = 'outgoing';

    /**
     * @var $status
     */
    private $status = 'all-ok';

    /**
     * @var $draftId
     */
    private $draftId;

    /**
     * @var $responseData
     */
    private $responseData;

    /**
     * Log constructor.
     *
     * @param   Context         $context
     * @param   LogFactory      $logFactory
     * @param   MessageManager  $messageManager
     */
    public function __construct(
        Context $context,
        LogFactory $logFactory,
        MessageManager $messageManager
    ) {
        $this->_logFactory     = $logFactory;
        $this->_messageManager = $messageManager;
        parent::__construct($context);
    }

    /**
     * @param   mixed  $direction
     */
    public function setDirection(string $direction): void
    {
        $this->direction = $direction;
    }

    /**
     * @param   mixed  $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @param   mixed  $draftId
     */
    public function setDraftId(string $draftId): void
    {
        $this->draftId = $draftId;
    }

    /**
     * @param   mixed  $responseData
     */
    public function setResponseData(string $responseData): void
    {
        $this->responseData = $responseData;
    }

    /**
     * @param   int    $entryId
     * @param   array  $data
     *
     * @return bool
     */
    public function editEntry($entryId, array $data)
    {
        try {
            /** @var LogModel $logEntry */
            $logEntry = $this->getFactory()->create();
            $logEntry->getResource()->load($logEntry, $entryId);
            unset($data['draft_id']);
            foreach ($data as $key => $value) {
                $logEntry->setData($key, $value);
            }
            $logEntry->getResource()->save($logEntry);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return LogFactory
     */
    public function getFactory()
    {
        return $this->_logFactory;
    }

    /**
     * @param   int  $entryId
     *
     * @return bool
     */
    public function deleteEntry($entryId)
    {
        try {
            /** @var LogModel $logEntry */
            $logEntry = $this->getFactory()->create();
            $logEntry->getResource()->load($logEntry, $entryId);
            $logEntry->getResource()->delete($logEntry);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $id
     *
     * @return DataObject
     */
    public function getEntryById($id)
    {
        /** @var LogModel $returnEntry */
        $logEntry = $this->getFactory()->create();

        /** @var LogModel $returnEntry */
        $logEntryCollection = $logEntry->getCollection();
        if (isset($logEntryCollection)) {
            $logEntryCollection->addFieldToFilter('id', ['eq' => $id]);
        }

        return $logEntryCollection->getFirstItem();
    }

    /**
     * @param $filter
     *
     * @return DataObject|LogModel
     */
    public function getEntry($filter)
    {
        /** @var LogModel $returnEntry */
        $logEntry = $this->getFactory()->create();

        /** @var LogModel $returnEntry */
        $returnEntry        = null;
        $logEntryCollection = $logEntry->getCollection();

        foreach ($filter['filter_array'] as $entryFilter) {
            $logEntryCollection->addFieldToFilter($entryFilter['field'],
                $entryFilter['condition']);
        }

        if (isset($logEntryCollection)) {
            $returnEntry = $logEntryCollection->getFirstItem();
        }

        return $returnEntry;
    }

    /**
     * @param   LogModel  $logEntry
     * @param   null      $data
     *
     * @return false|LogModel
     */
    public function updateEntry(LogModel $logEntry, $data = null)
    {
        try {
            if ($data === null) {
                $data = [];
            }

            $data['updated_at'] = time();

            $logEntry->addData($data);
            $logEntry->getResource()->save($logEntry);

            return $logEntry;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param   string  $apiUrl
     *
     * @return false|LogModel
     */
    public function createGetEntry(
        string $apiUrl
    ) {
        $this->setApiUrl($apiUrl);
        $this->setRequestType('get');

        return $this->createEntry();
    }

    /**
     * @param   mixed  $apiUrl
     */
    public function setApiUrl(string $apiUrl): void
    {
        $this->apiUrl = $apiUrl;
    }

    /**
     * @param   mixed  $requestType
     */
    public function setRequestType(string $requestType): void
    {
        $this->requestType = $requestType;
    }

    /**
     * @param   null  $data
     *
     * @return false|LogModel
     */
    public function createEntry($data = null)
    {
        try {
            if ($data === null) {
                $data = [];
            }

            $data               = $this->getLogDataArray($data);
            $data['created_at'] = time();

            $logEntry = $this->getFactory()->create();
            $logEntry->addData($data);
            $logEntry->getResource()->save($logEntry);

            return $logEntry;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param   array  $data
     *
     * @return array
     */
    public function getLogDataArray(array $data): array
    {
        if (!empty($this->apiUrl) && empty($data['api_url'])) {
            $data['api_url'] = $this->apiUrl;
        }

        if (isset($this->requestType) && empty($data['request_type'])) {
            $data['request_type'] = $this->requestType;
        }

        if (!empty($this->requestData) && empty($data['request_data'])) {
            $data['request_data'] = $this->requestData;
        }

        if (isset($this->direction) && empty($data['direction'])) {
            $data['direction'] = $this->direction;
        }

        if (!empty($this->status) && empty($data['status'])) {
            $data['status'] = $this->status;
        }

        if (!empty($this->draftId) && empty($data['draft_id'])) {
            $data['draft_id'] = $this->draftId;
        }

        if (isset($this->responseData) && empty($data['response_data'])) {
            $data['response_data'] = $this->responseData;
        }

        return $data;
    }

    /**
     * @param   string      $url
     * @param   array|null  $requestData
     *
     * @return false|LogModel
     */
    public function createPostEntry(
        string $url,
        array $requestData = null
    ) {
        $this->setApiUrl($url);
        $this->setRequestType('post');
        if (isset($requestData)) {
            $this->setRequestData(json_encode($requestData));
        }
        $result = $this->createEntry();

        return $result;
    }

    /**
     * @param   mixed  $requestData
     */
    public function setRequestData(string $requestData): void
    {
        $this->requestData = $requestData;
    }

    /**
     * @param   string      $url
     * @param   array|null  $requestData
     *
     * @return false|LogModel
     */
    public function createRedirectEntry(
        string $url,
        array $requestData = null
    ) {
        $this->setApiUrl($url);
        $this->setRequestType('redirect');
        if (isset($requestData)) {
            $this->setRequestData(json_encode($requestData));
        }
        $result = $this->createEntry();

        return $result;
    }

    /**
     * @param   string  $url
     * @param   array   $requestData
     *
     * @return false|LogModel
     */
    public function createPutEntry(
        string $url,
        array $requestData
    ) {
        $this->setApiUrl($url);
        $this->setRequestType('put');
        $this->setRequestData(json_encode($requestData));

        return $this->createEntry();
    }
}
