<?php
namespace Rissc\Printformer\Gateway\User;

use Rissc\Printformer\Gateway\Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Json\Decoder;
use Rissc\Printformer\Helper\Url as UrlHelper;
use Magento\Store\Model\StoreManagerInterface;
use Rissc\Printformer\Helper\Log as LogHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\UrlInterface;

class Draft
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var ZendClientFactor */
    protected $httpClientFactory;

    /** @var Decoder */
    protected $jsonDecoder;

    /** @var UrlHelper */
    protected $urlHelper;

    /** @var StoreManagerInterface */
    protected $_storeManager;

    /** @var LogHelper */
    protected $_logHelper;

    protected $_url;

    public function __construct(
        LoggerInterface $logger,
        ZendClientFactory $httpClientFactory,
        Decoder $jsonDecoder,
        UrlHelper $urlHelper,
        StoreManagerInterface $storeManager,
        LogHelper $logHelper,
        UrlInterface $url
    ) {
        $this->logger = $logger;
        $this->httpClientFactory = $httpClientFactory;
        $this->jsonDecoder = $jsonDecoder;
        $this->urlHelper = $urlHelper;
        $this->_storeManager = $storeManager;
        $this->_logHelper = $logHelper;
        $this->_url = $url;
    }

    /**
     * @param integer $draftId
     * @param integer $storeId
     * @throws Exception
     * @return \Rissc\Printformer\Gateway\User\Draft
     */
    public function deleteDraft($draftId, $storeId)
    {
        $url = $this->urlHelper
            ->setStoreId($storeId)
            ->getDraftDeleteUrl($draftId);

        $this->logger->debug($url);

        /** @var \Zend_Http_Response $response */
        $response = $this->httpClientFactory
            ->create()
            ->setUri((string)$url)
            ->setConfig(['timeout' => 30])
            ->request(\Zend_Http_Client::POST);

        if (!$response->isSuccessful()) {
            throw new Exception(__('Error deleting draft.'));
        }
        $responseArray = $this->jsonDecoder->decode($response->getBody());
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

    /**
     * @param \Rissc\Printformer\Gateway\User\Product $product
     * @param                                         $masterId
     *
     * @return null|string
     */
    public function createDraft($masterId, $intent)
    {
        $url      = null;
        $response = null;

        $_historyData = [
            'direction' => 'outgoing'
        ];

        $url = $this->urlHelper
            ->setStoreId($this->_storeManager->getStore()->getId())
            ->getDraftUrl();

        $_historyData['api_url'] = $url;

        $headers = [
            "X-Magento-Tags-Pattern: .*",
            "Content-Type: application/json"
        ];

        $postFields = [
            'masterId' => $masterId,
            'intent' => $this->getIntent($intent)
        ];

        $_historyData['request_data'] = json_encode($postFields);
        $_historyData['draft_id'] = $masterId;

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
            return null;
        }

        if(isset($curlResponse['data']['draftHash']))
        {
            $_historyData['status'] = 'send';
            $this->_logHelper->addEntry($_historyData);
            return (string)$curlResponse['data']['draftHash'];
        }

        $_historyData['status'] = 'failed';
        $this->_logHelper->addEntry($_historyData);
        return null;
    }

    /**
     * @param integer $draftId
     * @param integer $storeId
     * @throws Exception
     * @return \Rissc\Printformer\Gateway\User\Draft
     */
    public function getDraft($draftId)
    {
        $url = $this->urlHelper
            ->getDraftDeleteUrl($draftId);

        $this->logger->debug($url);

        /** @var \Zend_Http_Response $response */
        $response = $this->httpClientFactory
            ->create()
            ->setUri((string)$url)
            ->setConfig(['timeout' => 30])
            ->request(\Zend_Http_Client::POST);

        if (!$response->isSuccessful()) {
            throw new Exception(__('Error deleting draft.'));
        }
        $responseArray = $this->jsonDecoder->decode($response->getBody());
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

    public function getIntent($intent)
    {
        switch(strtolower($intent))
        {
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
