<?php
namespace Rissc\Printformer\Gateway\Admin;

use Magento\Framework\Json\Decoder;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Rissc\Printformer\Gateway\Exception;
use Magento\Framework\UrlInterface;
use Rissc\Printformer\Helper\Url as UrlHelper;
use Rissc\Printformer\Model\DraftFactory;
use Rissc\Printformer\Model\Draft as DraftModel;
use Rissc\Printformer\Helper\Log as LogHelper;

class Draft
{
    const API_URL_CALLBACKORDEREDSTATUS = 'callbackOrderedStatus';

    const DRAFT_PROCESSING_TYPE_SYNC    = 'sync';
    const DRAFT_PROCESSING_TYPE_ASYNC   = 'async';
    /** @var LoggerInterface */
    protected $logger;

    /** @var Decoder */
    protected $jsonDecoder;

    /** @var UrlHelper */
    protected $urlHelper;

    /** @var UrlInterface */
    protected $_urlInterface;

    /** @var DraftFactory */
    protected $_draftFactory;

    /** @var LogHelper */
    protected $_logHelper;

    public function __construct(
        LoggerInterface $logger,
        Decoder $jsonDecoder,
        UrlHelper $urlHelper,
        UrlInterface $urlInterface,
        DraftFactory $draftFactory,
        LogHelper $logHelper
    ) {
        $this->logger = $logger;
        $this->jsonDecoder = $jsonDecoder;
        $this->urlHelper = $urlHelper;
        $this->_urlInterface = $urlInterface;
        $this->_draftFactory = $draftFactory;
        $this->_logHelper = $logHelper;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 'sync', 'label' => __('Synchron')], ['value' => 'async', 'label' => __('Asynchron')]];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return ['sync' => __('Synchron'), 'async' => __('Asynchron')];
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return $this
     * @throws \Rissc\Printformer\Gateway\Exception
     */
    public function setDraftOrdered(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        $draftIds = [];
        $lastItem = null;
        $url      = null;
        $response = null;

        $_historyData = [
            'request_data' => null,
            'direction' => 'outgoing'
        ];

        foreach ($order->getAllItems() as $item) {
            if ($item->getPrintformerOrdered() || !$item->getPrintformerDraftid()) {
                continue;
            }
            $draftIds[] = $item->getPrintformerDraftid();
            $lastItem = $item;
        }

        if (!is_null($lastItem) && !empty($draftIds)) {
            $url = $this->urlHelper
                ->setStoreId($lastItem->getPrintformerStoreid())
                ->getDraftOrderedUrl($draftIds, $order->getQuoteId());

            $_historyData['request_data'] = json_encode([
                $draftIds,
                md5($order->getQuoteId())
            ]);
            $_historyData['api_url'] = $url;
            $_historyData['draft_id'] = implode(', ', $draftIds);

            $this->logger->debug($url);

            $headers = ["X-Magento-Tags-Pattern: .*"];

            $options = [
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false
            ];

            $response = $this->_curlRequest($url, $options);
            $this->logger->debug($response);
        } else {
            $this->logger->debug(__('Error setting draft ordered. Item is null or already ordered.'));
        }
        $_historyData['response_data'] = $response;
        if (empty($response))
        {
            $_historyData['status'] = 'failed';
            $this->_logHelper->addEntry($_historyData);
            throw new Exception(__('Error setting draft ordered. Empty Response: '. $response . ', Url: ' . $url));
        }
        $responseArray = $this->jsonDecoder->decode($response);

        if(!$responseArray['success']) {
            $_historyData['status'] = 'failed';
            $this->_logHelper->addEntry($_historyData);
            throw new Exception(__('Error setting draft ordered. Response success: false'));
        }

        if (!is_array($responseArray)) {
            $_historyData['status'] = 'failed';
            $this->_logHelper->addEntry($_historyData);
            throw new Exception(__('Error decoding response.'));
        }
        if (isset($responseArray['success']) && false == $responseArray['success']) {
            $errorMsg = 'Request was not successful.';
            if (isset($responseArray['error'])) {
                $errorMsg = $responseArray['error'];
            }
            $_historyData['status'] = 'failed';
            $this->_logHelper->addEntry($_historyData);
            throw new Exception(__($errorMsg));
        }

        foreach ($order->getAllItems() as $item) {
            if (!in_array($item->getPrintformerDraftid(), $draftIds)) {
                continue;
            }
            $item->setPrintformerOrdered(1);
        }

        $_historyData['status'] = 'send';
        $this->_logHelper->addEntry($_historyData);

        return $this;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @return bool
     */
    public function asyncDraftProcessor(Order $order)
    {
        $draftIds = [];
        $lastItem = null;
        $url      = null;
        $response = null;

        $_historyData = [
            'direction' => 'outgoing'
        ];

        foreach ($order->getAllItems() as $item) {
            if ($item->getPrintformerOrdered() || !$item->getPrintformerDraftid()) {
                continue;
            }
            $draftIds[] = $item->getPrintformerDraftid();
            $lastItem = $item;
        }

        if (!is_null($lastItem) && !empty($draftIds))
        {
            $url = $this->urlHelper
                ->setStoreId($lastItem->getPrintformerStoreid())
                ->getPdfProcessingUrl($draftIds);

            $_historyData['api_url'] = $url;

            $headers = [
                "X-Magento-Tags-Pattern: .*",
                "Content-Type: application/json"
            ];

            $postFields = [
                'draftIds' => $draftIds,
                'stateChangedNotifyUrl' => $this->_urlInterface->getUrl('rest/V1/printformer') . self::API_URL_CALLBACKORDEREDSTATUS
            ];

            $_historyData['request_data'] = json_encode($postFields);
            $_historyData['draft_id'] = implode(', ', $draftIds);

            $curlOptions = [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($postFields),
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false
            ];

            $curlResponse = json_decode($this->_curlRequest($url, $curlOptions), true);
            $_historyData['response_data'] = json_encode($curlResponse);
            if(isset($curlResponse['success']) && !$curlResponse['success'])
            {
                $_historyData['status'] = 'failed';
                $this->_logHelper->addEntry($_historyData);
                return false;
            }

            if(isset($curlResponse['processingId']))
            {
                /** @var DraftModel $draft */
                $draft = $this->_draftFactory->create();
                $draftCollection = $draft->getCollection()
                    ->addFieldToFilter('draft_id', ['in' => $draftIds]);

                if(count($draftCollection->getItems()))
                {
                    /** @var DraftModel $draftToUpdate */
                    foreach($draftCollection->getItems() as $draftToUpdate)
                    {
                        $draftToUpdate->setProcessingId($curlResponse['processingId']);
                        $draftToUpdate->getResource()->save($draftToUpdate);
                    }
                }

                foreach ($order->getAllItems() as $item) {
                    if (!in_array($item->getPrintformerDraftid(), $draftIds)) {
                        continue;
                    }
                    $item->setPrintformerOrdered(1);
                }

                $_historyData['status'] = 'send';
                $this->_logHelper->addEntry($_historyData);
                return true;
            }
        }

        $_historyData['status'] = 'failed';
        $this->_logHelper->addEntry($_historyData);
        return false;
    }

    protected function _curlRequest($url, $options)
    {
        $ch = curl_init($url);
        if (is_array($options))
        {
            foreach ($options as $key => $option)
            {
                curl_setopt($ch, $key, $option);
            }
        }
        $connectionTimeout = 5;
        $requestTimeout = 30;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectionTimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $requestTimeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $body = curl_exec($ch);
        curl_close($ch);

        return $body;
    }
}
