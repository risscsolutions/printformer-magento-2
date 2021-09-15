<?php

namespace Rissc\Printformer\Gateway\User;

use Rissc\Printformer\Gateway\Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Json\Decoder;
use Magento\Customer\Model\Session as CustomerSession;
use Rissc\Printformer\Helper\Api\Url as UrlHelper;
use Magento\Store\Model\StoreManagerInterface;
use Rissc\Printformer\Helper\Log as LogHelper;
use Magento\Framework\UrlInterface;
use GuzzleHttp\Client;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Rissc\Printformer\Helper\Api\Url;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Rissc\Printformer\Helper\Media;
use Rissc\Printformer\Helper\Config;
use Magento\Framework\Encryption\EncryptorInterface;

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

    /**
     * @var Client
     */
    protected $_httpClient;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var
     */
    protected $apiKey = null;

    /**
     * @var
     */
    protected $userIdentifier = null;

    protected $mediaHelper;

    /**
     * @var Config $_config
     */
    private $_config;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @param LoggerInterface $logger
     * @param ZendClientFactory $httpClientFactory
     * @param Decoder $jsonDecoder
     * @param CustomerSession $session
     * @param ScopeConfigInterface $scopeConfig
     * @param Url $urlHelper
     * @param StoreManagerInterface $storeManager
     * @param LogHelper $logHelper
     * @param UrlInterface $url
     * @param Media $mediaHelper
     * @param Config $config
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        LoggerInterface $logger,
        ZendClientFactory $httpClientFactory,
        Decoder $jsonDecoder,
        CustomerSession $session,
        ScopeConfigInterface $scopeConfig,
        UrlHelper $urlHelper,
        StoreManagerInterface $storeManager,
        LogHelper $logHelper,
        UrlInterface $url,
        Media $mediaHelper,
        Config $config,
        EncryptorInterface $encryptor
    ) {
        $this->_logger = $logger;
        $this->_httpClientFactory = $httpClientFactory;
        $this->_jsonDecoder = $jsonDecoder;
        $this->_urlHelper = $urlHelper;
        $this->_storeManager = $storeManager;
        $this->_logHelper = $logHelper;
        $this->_url = $url;
        $this->_customerSession = $session;
        $this->_scopeConfig = $scopeConfig;
        $this->mediaHelper = $mediaHelper;
        $this->encryptor = $encryptor;
        $this->_config = $config;
        $this->_urlHelper->initVersionHelper();
        $this->_httpClient = $this->getGuzzleClient();
    }

    /**
     * @return mixed
     */
    public function getClientApiKey()
    {
        if ($this->apiKey === null) {
            $encryptedKey = $this->_scopeConfig->getValue(Config::XML_PATH_V2_API_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            if (!empty($encryptedKey)){
                $decryptedKey = $this->encryptor->decrypt($encryptedKey);
                if (!empty($decryptedKey)){
                    $this->apiKey = $decryptedKey;
                }
            }
        }

        return $this->apiKey;
    }

    /**
     * @return string
     */
    public function createUser()
    {
        $url = $this->_urlHelper->getUser();
        if (!$url) {
            return '';
        }
        $response = $this->_httpClient->post($url);
        $response = json_decode($response->getBody(), true);

        return $response['data']['identifier'];
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getUserIdentifier()
    {
        $userIdentifier = $this->_customerSession->getPrintformerIdentification();
        if (!$userIdentifier) {
            if ($this->_customerSession->isLoggedIn()) {
                $customer = $this->_customerSession->getCustomer();
                $userIdentifier = $customer->getData('printformer_identification');
                if (!$userIdentifier) {
                    $userIdentifier = $this->createUser();
                    $customer->setData('printformer_identification', $userIdentifier);
                    $customer->getResource()->save($customer);
                }
            } else {
                $userIdentifier = $this->createUser();
            }

            $this->_customerSession->setPrintformerIdentification($userIdentifier);
        }

        return $userIdentifier;
    }

    public function setUserIdentifier($userIdentifier)
    {
        $this->userIdentifier = $userIdentifier;
    }

    public function getClientIdentifier()
    {
        return $this->_scopeConfig->getValue('printformer/version2group/v2identifier', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $redirectUrl
     * @return string
     */
    public function getRedirectUrl($redirectUrl)
    {
        /**
         * Create a valid JWT
         */
        $JWTBuilder = (new Builder())
            ->setIssuedAt(time())
            ->set('client', $this->getClientIdentifier())
            ->set('user', $this->getUserIdentifier())
            ->setId(bin2hex(random_bytes(16)), true)
            ->set('redirect', $redirectUrl)
            ->setExpiration($this->_config->getExpireDate());

        $JWT = (string)$JWTBuilder
            ->sign(new Sha256(), $this->getClientApiKey())
            ->getToken();

        $authUrl = $this->_urlHelper->getAuth();
        if (!$authUrl) {
            return '';
        }
        return $this->_urlHelper->getAuth() . '?' . http_build_query(['jwt' => $JWT]);
    }

    public function getPdfDocument($draftId)
    {
        /**
         * Create a valid JWT
         */
        $JWTBuilder = (new Builder())
            ->setIssuedAt(time())
            ->set('client', $this->getClientIdentifier())
            ->set('user', $this->getUserIdentifier())
            ->setId(bin2hex(random_bytes(16)), true)
            ->setExpiration($this->_config->getExpireDate());

        $JWT = (string)$JWTBuilder
            ->sign(new Sha256(), $this->getClientApiKey())
            ->getToken();
        return $this->_urlHelper->getPdfUrl($draftId) . '?' . http_build_query(['jwt' => $JWT]);
    }

    /**
     * @return Client
     */
    protected function getGuzzleClient()
    {
        $url = $this->_urlHelper
            ->setStoreId($this->_storeManager->getStore()->getId())
            ->getDraft();

        $header = [
            'Content-Type:' => 'application/json',
            'Accept' => 'application/json'
        ];
        $header['Authorization'] = 'Bearer ' . $this->getClientApiKey();

        return new Client([
            'base_uri' => $url,
            'headers' => $header,
        ]);
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
            ->getDraftDelete($draftId);

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

        $this->mediaHelper->deleteAllImages($draftId);

        return $this;
    }

    /**
     * @param $masterId
     * @param string $intent
     * @return null|string
     */
    public function createDraft($masterId, $intent = null, $userIdentifier = null)
    {
        $historyData = [
            'direction' => 'outgoing'
        ];

        $postFields = [
            'json' => [
                'master_id' => $masterId
            ]
        ];

            $postFields['json']['user_identifier'] = $this->getUserIdentifier();
        $postFields['json']['user_identifier'] = $this->getUserIdentifier();

        if ($intent !== null) {
            $postFields['intent'] = $this->getIntent($intent);
        }

        $historyData['request_data'] = json_encode($postFields);
        $historyData['draft_id'] = $masterId;

        $url = $this->_urlHelper
            ->setStoreId($this->_storeManager->getStore()->getId())
            ->getDraft();

        $response = $this->_httpClient->post($url, $postFields);
        $response = json_decode($response->getBody(), true);

        $draftHash = $response['data']['draftHash'];

        if (!isset($draftHash)) {
            $historyData['status'] = 'failed';
            $this->_logHelper->addEntry($historyData);
            return null;
        }

        if (isset($draftHash)) {
            $historyData['status'] = 'send';
            $this->_logHelper->addEntry($historyData);
            return $draftHash;
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
            ->getDraft($draftId);

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
        switch (strtolower($intent)) {
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
