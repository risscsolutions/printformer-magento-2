<?php

namespace Rissc\Printformer\Gateway\User;

use DateTimeImmutable;
use GuzzleHttp\ClientFactory;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Magento\Framework\Exception\NoSuchEntityException;
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
use Rissc\Printformer\Helper\Api;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Rissc\Printformer\Helper\Media;
use Rissc\Printformer\Helper\Config;
use Magento\Framework\Encryption\EncryptorInterface;
use Rissc\Printformer\Model\Draft as DraftModel;
use Zend_Http_Client;
use Zend_Http_Response;

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
     * @var Api
     */
    protected $apiHelper;

    /**
     * @var Config $_config
     */
    private $_config;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var Configuration
     */
    private $jwtConfig;

    private ClientFactory $clientFactory;

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
     * @param ClientFactory $clientFactory
     * @param Api $apiHelper
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
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
        EncryptorInterface $encryptor,
        ClientFactory $clientFactory,
        Api $apiHelper
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
        $storeId = $storeManager->getStore()->getId();
        $websiteId = $storeManager->getWebsite()->getId();
        $this->clientFactory = $clientFactory;
        $this->_httpClient = $this->getGuzzleClient($storeId, $websiteId);
        $this->apiHelper = $apiHelper;

        try {
            $apiKey = $this->_config->getClientApiKey($storeId, $websiteId);
            if (!empty($apiKey)) {
                $this->jwtConfig = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($apiKey));
            }
        } catch (NoSuchEntityException $e) {
        }
    }

    /**
     * @return mixed
     */
    public function getClientApiKey($storeId, $websiteId)
    {
        $apiKey = $this->_config->getClientApiKey($storeId, $websiteId);
        if (!empty($apiKey)){
            $this->apiKey = $apiKey;
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

        $createdEntry = $this->_logHelper->createPostEntry($url);
        $response = $this->_httpClient->post($url);
        $this->_logHelper->updateEntry($createdEntry, ['response_data' => $response->getBody()->getContents()]);

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
                    $customer->getResource()->saveAttribute($customer, 'printformer_identification');
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

    /**
     * @param $redirectUrl
     * @return string
     */
    public function getRedirectUrl($redirectUrl)
    {
        $client = $this->_config->getClientIdentifier();
        $identifier = bin2hex(random_bytes(16));
        $issuedAt = new DateTimeImmutable();
        $expirationDate = $this->_config->getExpireDate();
        $JWTBuilder = $this->jwtConfig->builder()
            ->issuedAt($issuedAt)
            ->withClaim('client', $client)
            ->withClaim('user', $this->getUserIdentifier())
            ->identifiedBy($identifier)
            ->withClaim('redirect', $redirectUrl)
            ->expiresAt($expirationDate)
            ->withHeader('jti', $identifier);
        $JWT = $JWTBuilder->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey())->toString();

        $authUrl = $this->_urlHelper->getAuth();
        if (!$authUrl) {
            return '';
        }

        return $this->_urlHelper->getAuth() . '?' . http_build_query(['jwt' => $JWT]);
    }

    public function getPdfDocument($draftId)
    {
        $client = $this->_config->getClientIdentifier();
        $identifier = bin2hex(random_bytes(16));
        $issuedAt = new DateTimeImmutable();
        $expirationDate = $this->_config->getExpireDate();
        $JWTBuilder = $this->jwtConfig->builder()
            ->issuedAt($issuedAt)
            ->withClaim('client', $client)
            ->withClaim('user', $this->getUserIdentifier())
            ->identifiedBy($identifier)
            ->expiresAt($expirationDate)
            ->withHeader('jti', $identifier);
        $JWT = $JWTBuilder->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey())->toString();

        return $this->_urlHelper->getPdfUrl($draftId) . '?' . http_build_query(['jwt' => $JWT]);
    }

    /**
     * @return Client
     */
    protected function getGuzzleClient($storeId, $websiteId)
    {
        $url = $this->_urlHelper->getDraft();

        $header = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
        $header['Authorization'] = 'Bearer ' . $this->getClientApiKey($storeId, $websiteId);

        return $this->clientFactory->create(
            [
                'config' => [
                    'base_uri' => $url,
                    'headers' => $header,
                ],
            ],
        );
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
            ->getDraftDelete($draftId);

        $createdEntry = $this->_logHelper->createPostEntry($url);
        /** @var Zend_Http_Response $response */
        $response = $this->_httpClientFactory
            ->create()
            ->setUri((string)$url)
            ->setConfig(['timeout' => 30])
            ->request(Zend_Http_Client::POST);
        $this->_logHelper->updateEntry($createdEntry, ['response_data' => $response->getBody()]);

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
     * @param $identifier
     * @param string $intent
     * @return null|string
     */
    public function createDraft($identifier, $intent = null)
    {
        $url = $this->_urlHelper
            ->getDraft();

        $historyData = [
            'direction' => 'outgoing'
        ];

        $requestData = [
            'json' => [
                'identifier' => $identifier
            ]
        ];

        $requestData['json']['user_identifier'] = $this->getUserIdentifier();
        $userGroupIdentifier = $this->apiHelper->getUserGroupIdentifier();
        if (!empty($userGroupIdentifier)) {
            $requestData['json'][DraftModel::KEY_USER_GROUP_IDENTIFIER] = $userGroupIdentifier;
        }


        if ($intent !== null) {
            $requestData['intent'] = $this->getIntent($intent);
        }

        $historyData['request_data'] = json_encode($requestData);
        $historyData['draft_id'] = $identifier;

        $createdEntry = $this->_logHelper->createPostEntry($url, $requestData);
        $response = $this->_httpClient->post($url, $requestData);
        $this->_logHelper->updateEntry($createdEntry, ['response_data' => $response->getBody()->getContents()]);

        $response = json_decode($response->getBody(), true);

        $draftHash = $response['data']['draftHash'];

        if (!isset($draftHash)) {
            $historyData['status'] = 'failed';
            $this->_logHelper->createEntry($historyData);
            return null;
        }

        if (isset($draftHash)) {
            $historyData['status'] = 'send';
            $this->_logHelper->createEntry($historyData);
            return $draftHash;
        }

        $historyData['status'] = 'failed';
        $this->_logHelper->createEntry($historyData);
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

        $createdEntry = $this->_logHelper->createPostEntry($url);
        /** @var Zend_Http_Response $response */
        $response = $this->_httpClientFactory
            ->create()
            ->setUri((string)$url)
            ->setConfig(['timeout' => 30])
            ->request(Zend_Http_Client::POST);
        $this->_logHelper->updateEntry($createdEntry, ['response_data' => $response->getBody()]);

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
