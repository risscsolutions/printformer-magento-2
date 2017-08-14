<?php

namespace Rissc\Printformer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Message\Manager as MessageManager;
use Rissc\Printformer\Model\History\LogFactory;
use Rissc\Printformer\Model\History\Log as LogModel;

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
     * Log constructor.
     * @param Context $context
     * @param LogFactory $logFactory
     * @param MessageManager $messageManager
     */
    public function __construct(
        Context $context,
        LogFactory $logFactory,
        MessageManager $messageManager
    ) {
        $this->_logFactory = $logFactory;
        $this->_messageManager = $messageManager;
        parent::__construct($context);
    }

    /**
     * @return LogFactory
     */
    public function getFactory()
    {
        return $this->_logFactory;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    public function addEntry(array $data)
    {
        try {
            /** @var LogModel $logEntry */
            $logEntry = $this->getFactory()->create();
            $data['created_at'] = time();
            $logEntry->addData($data);
            $logEntry->getResource()->save($logEntry);

            return true;
        } catch(\Exception $e) {
            return false;
        }
    }

    /**
     * @param int $entryId
     * @param array $data
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
            foreach($data as $key => $value) {
                $logEntry->setData($key, $value);
            }
            $logEntry->getResource()->save($logEntry);

            return true;
        } catch(\Exception $e) {
            return false;
        }
    }

    /**
     * @param int $entryId
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
        } catch(\Exception $e) {
            return false;
        }
    }

    public function getEntry($filter)
    {
        /** @var LogModel $returnEntry */
        $logEntry = $this->getFactory()->create();

        /** @var LogModel $returnEntry */
        $returnEntry = null;
        if(is_array($filter) && !empty($filter['filter_array'])) {
            $logEntryCollection = $logEntry->getCollection();
            foreach($filter['filter_array'] as $entryFilter) {
                $logEntryCollection->addFieldToFilter($entryFilter['field'], $entryFilter['condition']);
            }

            $returnEntry = $logEntryCollection->getFirstItem();
        } elseif (is_numeric($filter)) {
            $returnEntry = $logEntry->getResource()->load($logEntry, $filter);
        }

        return $returnEntry;
    }
}