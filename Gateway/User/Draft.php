<?php

namespace Rissc\Printformer\Gateway\User;

use Rissc\Printformer\Gateway\Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Json\Decoder;
use Rissc\Printformer\Helper\Url as UrlHelper;
use Magento\Store\Model\StoreManagerInterface;
use Rissc\Printformer\Helper\Log as LogHelper;
use Magento\Framework\UrlInterface;
use \Rissc\Printformer\Model\ProductFactory;

class Draft
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var ZendClientFactory
     */
    protected $_httpClientFactory;

    /**
     * @var Decoder
     */
    protected $_jsonDecoder;

    /**
     * @var UrlHelper
     */
    protected $_urlHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var LogHelper
     */
    protected $_logHelper;

    /**
     * @var UrlInterface
     */
    protected $_url;

    /** @var  database connection */
    protected $_connection;

    public function __construct(
        LoggerInterface $logger,
        ZendClientFactory $httpClientFactory,
        ProductFactory $printformerProductFactory,
        Decoder $jsonDecoder,
        UrlHelper $urlHelper,
        StoreManagerInterface $storeManager,
        LogHelper $logHelper,
        UrlInterface $url
    ) {
        $this->_logger = $logger;
        $this->_httpClientFactory = $httpClientFactory;
        $this->_jsonDecoder = $jsonDecoder;
        $this->_urlHelper = $urlHelper;
        $this->_storeManager = $storeManager;
        $this->_logHelper = $logHelper;
        $this->_url = $url;

        $printformerProduct = $printformerProductFactory->create();
        $this->_connection = $printformerProduct->getResource()->getConnection();
    }

    /**
     * @param string $draftId
     * @param int $storeId
     * @return $this
     * @throws Exception
     */
    public function deleteDraft($draftId, $storeId)
    {
        $url = $this->_urlHelper
            ->setStoreId($storeId)
            ->getDraftDeleteUrl($draftId);

        $this->_logger->debug($url);

        /** @var \Zend_Http_Response $response */
        $response = $this->_httpClientFactory
            ->create()
            ->setUri((string)$url)
            ->setConfig(['timeout' => 30])
            ->request(\Zend_Http_Client::DELETE);

        //delete draft if it was successfully deleted in printformer or if it was already deleted
        if($response->isSuccessful() || $response->getStatus() == 404) {
            $this->_logger->debug("delete Draft: ".$draftId);
            //sql query to delete the draft from magento's database
            $query = "DELETE FROM `" . $this->_connection->getTableName('printformer_draft') . "` WHERE `draft_id` = '$draftId' AND `store_id` = $storeId";
            $this->_connection->query($query);
            return $this;
        }

        if (!$response->isSuccessful()) {
            throw new Exception(__('Error deleting draft.'));
        }
        $responseArray = $this->_jsonDecoder->decode($response->getBody());
        if (!is_array($responseArray)) {
            throw new Exception(__('Error decoding response.'));
        }
        if (isset($responseArray['success']) && false == $responseArray['success']) {
            $errorMsg = 'Request was not successful.';
            if (isset($responseArray['error'])) {
                $errorMsg = $responseArray['error'];
            }
            throw new Exception(__($errorMsg));
        }
    }

    /**
     * @param $masterId
     * @param string $intent
     * @return null|string
     */
    public function createDraft($masterId, $intent = null)
    {
        $url      = null;
        $response = null;

        $historyData = [
            'direction' => 'outgoing'
        ];

        $url = $this->_urlHelper
            ->setStoreId($this->_storeManager->getStore()->getId())
            ->getDraftUrl();

        $historyData['api_url'] = $url;

        $headers = [
            "X-Magento-Tags-Pattern: .*",
            "Content-Type: application/json"
        ];

        $postFields = [
            'masterId' => $masterId
        ];

        if($intent !== null) {
            $postFields['intent'] = $this->getIntent($intent);
        }

        $historyData['request_data'] = json_encode($postFields);
        $historyData['draft_id'] = $masterId;

        $curlOptions = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postFields),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false
        ];

        $curlResponse = json_decode($this->_curlRequest($url, $curlOptions), true);
        $historyData['response_data'] = json_encode($curlResponse);
        if(isset($curlResponse['success']) && !$curlResponse['success']) {
            $historyData['status'] = 'failed';
            $this->_logHelper->addEntry($historyData);
            return null;
        }

        if(isset($curlResponse['data']['draftHash'])) {
            $historyData['status'] = 'send';
            $this->_logHelper->addEntry($historyData);
            return (string)$curlResponse['data']['draftHash'];
        }

        $historyData['status'] = 'failed';
        $this->_logHelper->addEntry($historyData);
        return null;
    }

    /**
     * @param int $draftId
     * @return $this
     * @throws Exception
     */
    public function getDraft($draftId)
    {
        $url = $this->_urlHelper
            ->getDraftDeleteUrl($draftId);

        $this->_logger->debug($url);

        /** @var \Zend_Http_Response $response */
        $response = $this->_httpClientFactory
            ->create()
            ->setUri((string)$url)
            ->setConfig(['timeout' => 30])
            ->request(\Zend_Http_Client::POST);

        if (!$response->isSuccessful()) {
            throw new Exception(__('Error deleting draft.'));
        }
        $responseArray = $this->_jsonDecoder->decode($response->getBody());
        if (!is_array($responseArray)) {
            throw new Exception(__('Error decoding response.'));
        }
        if (isset($responseArray['success']) && false == $responseArray['success']) {
            $errorMsg = 'Request was not successful.';
            if (isset($responseArray['error'])) {
                $errorMsg = $responseArray['error'];
            }
            throw new Exception(__($errorMsg));
        }

        return $this;
    }

    protected function _curlRequest($url, $options)
    {
        $ch = curl_init($url);
        if (is_array($options)) {
            foreach ($options as $key => $option) {
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

    public function getIntent($intent)
    {
        switch(strtolower($intent)) {
            case 'editor':
                return 'customize';
                break;
            case 'personalizations':
                return 'personalize';
                break;
            case 'upload':
                return 'upload';
                break;
            case 'upload and editor':
                return 'upload-and-editor';
                break;
        }

        return $intent;
    }
}
