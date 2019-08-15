<?php
namespace Rissc\Printformer\Helper;

use GuzzleHttp\Exception\ServerException;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use GuzzleHttp\Client;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\Store;
use Rissc\Printformer\Helper\Api\Url as UrlHelper;
use Magento\Store\Model\StoreManagerInterface;
use Rissc\Printformer\Model\Draft;
use Rissc\Printformer\Model\DraftFactory;
use GuzzleHttp\Psr7\Stream as Psr7Stream;
use Rissc\Printformer\Helper\Session as SessionHelper;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Backend\Model\Session as AdminSession;

class Api extends AbstractHelper
{
    const API_URL_CALLBACKORDEREDSTATUS = 'callbackOrderedStatus';

    /** @var UrlHelper */
    protected $_urlHelper;

    /** @var CustomerSession */
    protected $_customerSession;

    /** @var SessionHelper */
    protected $_sessionHelper;

    /** @var StoreManagerInterface */
    protected $_storeManager;

    /** @var DraftFactory */
    protected $_draftFactory;

    /** @var Config */
    protected $_config;

    /** @var CustomerFactory */
    protected $_customerFactory;

    /** @var CustomerResource */
    protected $_customerResource;

    /** @var Client[] */
    protected $_httpClients = [];

    /** @var int */
    protected $_storeId = Store::DEFAULT_STORE_ID;

    /** @var AdminSession */
    protected $_adminSession;

    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        UrlHelper $urlHelper,
        StoreManagerInterface $storeManager,
        DraftFactory $draftFactory,
        SessionHelper $sessionHelper,
        Config $config,
        CustomerFactory $customerFactory,
        CustomerResource $customerResource,
        AdminSession $adminSession
    ) {
        $this->_customerSession = $customerSession;
        $this->_urlHelper = $urlHelper;
        $this->_storeManager = $storeManager;
        $this->_draftFactory = $draftFactory;
        $this->_sessionHelper = $sessionHelper;
        $this->_config = $config;
        $this->_customerFactory = $customerFactory;
        $this->_customerResource = $customerResource;
        $this->_adminSession = $adminSession;

        $this->setStoreId($storeManager->getStore()->getId());

        $this->_urlHelper->setStoreId($this->getStoreId());
        $this->_config->setStoreId($this->getStoreId());

        $this->apiUrl()->initVersionHelper();
        $this->apiUrl()->setStoreManager($storeManager);

        parent::__construct($context);
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->_storeId;
    }

    /**
     * @param int $storeId
     *
     * @return $this
     */
    public function setStoreId(int $storeId)
    {
        $this->_storeId = $storeId;

        return $this;
    }

    /**
     * @return Client
     */
    public function getHttpClient()
    {
        if (!isset($this->_httpClients[$this->getStoreId()])) {
            $this->_httpClients[$this->getStoreId()] = new Client([
                'base_url' => $this->apiUrl()->setStoreId($this->getStoreId())->getPrintformerBaseUrl(),
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->_config->setStoreId($this->getStoreId())->getClientApiKey(),
                ]
            ]);
        }

        return $this->_httpClients[$this->getStoreId()];
    }

    /**
     * @return Config
     */
    public function config()
    {
        return $this->_config;
    }

    /**
     * @return StoreManagerInterface
     */
    public function getStoreManager()
    {
        return $this->_storeManager;
    }

    /**
     * @param $customer Customer
     */
    public function checkUserData($customer)
    {
        if ($customer->getPrintformerIdentification() !== null) {
            $userData = $this->getHttpClient()->get(
                $this->apiUrl()->setStoreId($this->getStoreId())->getUserData(
                    $customer->getPrintformerIdentification()
                )
            );

            if ($userData->getStatusCode() === 200) {
                $resultData = json_decode($userData->getBody()->getContents(), true);
                $profileData = $resultData['data']['profile'];

                if ($profileData['firstName'] == '' || $profileData['lastName'] == '') {
                    $options = [
                        'json' => [
                            'firstName' => $customer->getFirstname(),
                            'lastName' => $customer->getLastname(),
                            'email' => $customer->getEmail()
                        ]
                    ];

                    $this->getHttpClient()->put(
                        $this->apiUrl()->setStoreId($this->getStoreId())->getUserData(
                            $customer->getPrintformerIdentification()
                        ), $options
                    );
                }
            }
        } else {

            $options = [
                'json' => [
                    'firstName' => $customer->getFirstname(),
                    'lastName' => $customer->getLastname(),
                    'email' => $customer->getEmail()
                ]
            ];

            $userIdentifier = $this->createUser($options);
            $customer->setData('printformer_identification', $userIdentifier);
        }

    }

    /**
     * @param Customer|int $customer
     * @param null $admin
     *
     * @return string
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function getUserIdentifier($customer = null, $admin = null)
    {
        $stores = $this->getStoreManager()->getStores(true, false);

        foreach ($stores as $store) {
            if ($store->getCode() == 'admin') {
                if ($admin !== null) {
                    if (!$admin->getData('printformer_identification')) {
                        $adminUserIdentifier = $this->createUser();
                        $connection = $admin->getResource()->getConnection();
                        $connection->query("
                        UPDATE " . $connection->getTableName('admin_user') . "
                        SET
                            `printformer_identification` = '" . $adminUserIdentifier . "'
                        WHERE
                            `user_id` = " . $admin->getId() . ";
                    ");
                        $this->_adminSession->setPrintformerIdentification($adminUserIdentifier);
                    } else {
                        if ($admin->getData('printformer_identification') != $this->_adminSession->getPrintformerIdentification()) {
                            $this->_adminSession->setPrintformerIdentification($admin->getData('printformer_identification'));
                        }
                    }

                    return $this->_adminSession->getPrintformerIdentification();
                }
            }

            continue;
        }

        if (!$this->_config->isV2Enabled()) {
            return null;
        }

        if (is_numeric($customer) && $customer === 0) {
            return null;
        }

        $customerModel = null;
        if (!empty($customer) && is_numeric($customer)) {

            /** @var Customer $customerModel */
            $customerModel = $this->_customerFactory->create();
            $this->_customerResource->load($customerModel, $customer);

            $customer = $customerModel;
            $this->checkUserData($customer);
        }

        if ($customer !== null) {
            return $customer->getPrintformerIdentification();
        }

        if ($this->_customerSession->isLoggedIn()) {
            $customer = $this->_customerSession->getCustomer();
            $customer->getResource()->load($customer, $customer->getId());

            if (!$customer->getData('printformer_identification')) {
                $customerUserIdentifier = $this->createUser();
                $connection = $customer->getResource()->getConnection();
                $connection->query("
                    UPDATE " . $connection->getTableName('customer_entity') . "
                    SET
                        `printformer_identification` = '" . $customerUserIdentifier . "'
                    WHERE
                        `entity_id` = " . $customer->getId() . ";
                ");
                $customer->setData('printformer_identification', $customerUserIdentifier);
                $customer->getResource()->save($customer);
                $this->_customerSession->setPrintformerIdentification($customerUserIdentifier);
            } else {
                if ($customer->getData('printformer_identification') !=
                    $this->_customerSession->getPrintformerIdentification()) {
                    $this->_customerSession->setPrintformerIdentification(
                        $customer->getData('printformer_identification')
                    );
                }
            }

            $this->checkUserData($customer);

        } else {
            if (!$this->_customerSession->getPrintformerIdentification()) {
                $guestUserIdentifier = $this->createUser();
                $this->_customerSession->setPrintformerIdentification($guestUserIdentifier);
            }
        }

        return $this->_customerSession->getPrintformerIdentification();
    }

    /**
     * @return UrlHelper
     */
    public function apiUrl()
    {
        $this->_urlHelper->setStoreId($this->getStoreId());

        return $this->_urlHelper;
    }

    /**
     * @param array $userOptions
     * @return string
     */
    public function createUser($userOptions = [])
    {
        $url = $this->apiUrl()->setStoreId($this->getStoreId())->getUser();

        $response = $this->getHttpClient()->post($url, $userOptions);
        $response = json_decode($response->getBody(), true);

        return $response['data']['identifier'];
    }

    /**
     * @param int    $masterId
     * @param string $userIdentifier
     * @param array  $params
     *
     * @return mixed
     */
    public function createDraftHash($masterId, $userIdentifier, $params = [])
    {
        $url = $this->apiUrl()->setStoreId($this->getStoreId())->getDraft();

        $options = [
            'json' => [
                'master_id' => $masterId,
                'user_identifier' => $userIdentifier
            ]
        ];

        foreach($params as $key => $value) {
            $options['json'][$key] = $value;
        }

        $response = $this->getHttpClient()->post($url, $options);
        $response = json_decode($response->getBody(), true);
        if ($this->_sessionHelper->hasDraftInCache($response['data']['draftHash'])) {
            $this->_sessionHelper->updateDraftInCache($response['data']['draftHash'], $response['data']);
        } else {
            $this->_sessionHelper->addDraftToCache($response['data']['draftHash'], $response['data']);
        }

        return $response['data']['draftHash'];
    }

    /**
     * @param string $oldDraftId
     * @return string
     */
    public function getReplicateDraftId(string $oldDraftId) : string
    {
        if ($this->_sessionHelper->hasDraftInCache($oldDraftId)) {
            $this->_sessionHelper->removeDraftFromCache($oldDraftId);
        }
        $url = $this->apiUrl()->setStoreId($this->getStoreId())->getReplicateDraftId($oldDraftId);

        $response = $this->getHttpClient()->get($url);
        $draftInfo = json_decode($response->getBody(), true);

        $draftHash = $draftInfo['data']['draftHash'];

        if ($this->_sessionHelper->hasDraftInCache($draftHash)) {
            $this->_sessionHelper->updateDraftInCache($draftHash, $draftInfo['data']);
        } else {
            $this->_sessionHelper->addDraftToCache($draftHash, $draftInfo['data']);
        }

        return $draftHash;
    }

    /**
     * @param string $draftHash
     * @param bool $forceUpdate
     *
     * @return array
     */
    public function getPrintformerDraft($draftHash, $forceUpdate = false)
    {
        if (!$this->_sessionHelper->hasDraftInCache($draftHash) || $forceUpdate) {
            $url = $this->apiUrl()->setStoreId($this->getStoreId())->getDraft($draftHash);

            $response = $this->getHttpClient()->get($url);
            $response = json_decode($response->getBody(), true);

            if ($forceUpdate && $this->_sessionHelper->hasDraftInCache($response['data']['draftHash'])) {
                $this->_sessionHelper->updateDraftInCache($response['data']['draftHash'], $response['data']);
            } else {
                $this->_sessionHelper->addDraftToCache($response['data']['draftHash'], $response['data']);
            }
        }

        return $this->_sessionHelper->getDraftCache($draftHash);
    }

    /**
     * @param string $draftHash
     * @param string $userIdentifier
     * @param array  $params
     *
     * @return string
     * @throws \Exception
     */
    public function getEditorWebtokenUrl($draftHash, $userIdentifier, $params = [])
    {
        $editorOpenUrl = $this->apiUrl()->getEditor($draftHash, null, $params);

        $JWTBuilder = (new Builder())
            ->setIssuedAt(time())
            ->set('client', $this->_config->getClientIdentifier($this->getStoreId()))
            ->set('user', $userIdentifier)
            ->setId(bin2hex(random_bytes(16)), true)
            ->set('redirect', $editorOpenUrl)
            ->setExpiration($this->_config->getExpireDate());

        $JWT = (string)$JWTBuilder
            ->sign(new Sha256(), $this->_config->getClientApiKey($this->getStoreId()))
            ->getToken();

        return $this->apiUrl()->getAuth() . '?' . http_build_query(['jwt' => $JWT]);
    }

    /**
     * @param string $draftHash
     * @param int    $masterId
     * @param int    $productId
     * @param string $intent
     * @param string $sessionUniqueId
     * @param int    $customerId
     * @param int    $printformerProductId
     *
     * @return Draft
     * @throws \Exception
     */
    public function draftProcess(
        $draftHash = null,
        $masterId = null,
        $productId = null,
        $intent = null,
        $sessionUniqueId = null,
        $customerId = null,
        $printformerProductId = null,
        $checkOnly = false
    ) {
        $store = $this->_storeManager->getStore();

        $process = $this->getDraftProcess($draftHash, $productId, $intent, $sessionUniqueId);
        if(!$process->getId() && !$checkOnly) {
            $dataParams = [
                'intent' => $intent
            ];

            if (!$draftHash) {
                $draftHash = $this->createDraftHash($masterId, $this->getUserIdentifier(), $dataParams);
            }

            $process->addData([
                'draft_id' => $draftHash,
                'store_id' => $store->getId(),
                'intent' => $intent,
                'session_unique_id' => $sessionUniqueId,
                'product_id' => $productId,
                'customer_id' => $customerId,
                'user_identifier' => $this->getUserIdentifier(),
                'created_at' => time(),
                'printformer_product_id' => $printformerProductId
            ]);
            $process->getResource()->save($process);
        }

        return $process;
    }

    /**
     * @param string $draftHash
     * @param int    $productId
     * @param string $intent
     * @param string $sessionUniqueId
     * @param int    $printformerProductId
     *
     * @return \Magento\Framework\DataObject|Draft
     * @throws \Exception
     */
    protected function getDraftProcess(
        $draftHash = null,
        $productId = null,
        $intent = null,
        $sessionUniqueId = null,
        $printformerProductId = null
    ) {
        /** @var Draft $process */
        $process = $this->_draftFactory->create();

        $draftCollection = $process->getCollection();
        if($draftHash !== null) {
            $draftCollection->addFieldToFilter('draft_id', ['eq' => $draftHash]);
        } else {
            if($intent !== null) {
                $draftCollection->addFieldToFilter('intent', ['eq' => $intent]);
            }
            $draftCollection->addFieldToFilter('session_unique_id', ['eq' => $sessionUniqueId]);
            $draftCollection->addFieldToFilter('product_id', ['eq' => $productId]);
        }
        if($printformerProductId !== null) {
            $draftCollection->addFieldToFilter('printformer_product_id', ['eq' => $printformerProductId]);
        }
        if ($draftCollection->count() == 1) {
            $process = $draftCollection->getFirstItem();
            if ($process->getId() && $process->getDraftId()) {
                $this->_sessionHelper->setCurrentIntent($process->getIntent());
            }
        } else {
            $process = $draftCollection->getLastItem();
        }

        return $process;
    }

    /**
     * @param $processId
     *
     * @return Draft
     */
    public function loadDraftProcess($processId)
    {
        /** @var Draft $process */
        $process = $this->_draftFactory->create();
        $process->getResource()->load($process, $processId);

        return $process;
    }

    /**
     * @param $draftIds
     *
     * @throws \Exception
     */
    public function setAsyncOrdered($draftIds)
    {
        $draftProcessingUrl = $this->apiUrl()->setStoreId($this->getStoreId())->setStoreId($this->_storeManager->getStore()->getId())->getDraftProcessing($draftIds);
        $stateChangedNotifyUrl = $this->_urlBuilder->getUrl('rest/V1/printformer') . self::API_URL_CALLBACKORDEREDSTATUS;

        $postFields = [
            'json' => [
                'draftIds' => $draftIds,
                'stateChangedNotifyUrl' => $stateChangedNotifyUrl
            ]
        ];

        $response = $this->getHttpClient()->post($draftProcessingUrl, $postFields);

        $responseArray = json_decode($response->getBody(), true);
        $processingHash = !empty($responseArray['processingId']) ? $responseArray['processingId'] : null;

        if($processingHash !== null) {
            foreach ($draftIds as $draftHash) {
                /** @var Draft $process */
                $process = $this->getDraftProcess($draftHash);
                if ($process->getId()) {
                    $process->setProcessingId($processingHash);
                    $process->setProcessingStatus(1);
                    $process->getResource()->save($process);
                }
            }
        }
    }

    /**
     * @param array $parsed_url
     *
     * @return string
     */
    protected function unparseUrl($parsed_url) {
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';

        return $scheme . $user . $pass . $host . $port . $path . $query . $fragment;
    }

    /**
     * @param string $stringStatus
     *
     * @return int
     */
    public function getMappedProcessingStatus($stringStatus)
    {
        switch($stringStatus) {
            case 'processed':
                return 1;
                break;
            case 'failed':
                return 0;
                break;
            case 'pending':
            case 'in-process':
                return 2;
                break;
        }

        return -1;
    }

    /**
     * @param string $draftHash
     * @param string $userIdentifier
     * @param int $width
     * @param int $height
     * @param int $page
     * @return array
     */
    public function getThumbnail($draftHash, $userIdentifier, $width, $height, $page = 1)
    {
        $httpClient = new Client([
            'base_url' => $this->apiUrl()->setStoreId($this->getStoreId())->getPrintformerBaseUrl(),
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);

        $thumbnailUrl = $this->apiUrl()->setStoreId($this->getStoreId())->getThumbnail($draftHash);

        $JWTBuilder = (new Builder())
            ->setIssuedAt(time())
            ->set('client', $this->_config->setStoreId($this->getStoreId())->getClientIdentifier())
            ->set('user', $userIdentifier)
            ->setExpiration($this->_config->setStoreId($this->getStoreId())->getExpireDate());

        $JWT = (string)$JWTBuilder
            ->sign(new Sha256(), $this->_config->setStoreId($this->getStoreId())->getClientApiKey())
            ->getToken();

        $postFields = [
            'jwt' => $JWT,
            'width' => $width,
            'height' => $height,
            'page' => $page
        ];

        try {
            $response = $httpClient->get($thumbnailUrl . '?' . http_build_query($postFields));
        } catch(ServerException $e) {
            throw $e;
        }


        /** @var Psr7Stream $stream */
        $stream = $response->getBody();
        $responseData = [
            'content_type' => implode('', $response->getHeader('Content-Type')),
            'size' => $stream->getSize(),
            'content' => $stream->getContents()
        ];

        return $responseData;
    }

    /**
     * @param $draftHash
     *
     * @return string
     * @throws \Exception
     */
    public function getPdfLink($draftHash)
    {
        $JWTBuilder = (new Builder())
            ->setIssuedAt(time())
            ->set('client', $this->_config->setStoreId($this->getStoreId())->getClientIdentifier())
            ->setExpiration($this->_config->setStoreId($this->getStoreId())->getExpireDate());

        $JWT = (string)$JWTBuilder
            ->sign(new Sha256(), $this->_config->setStoreId($this->getStoreId())->getClientApiKey())
            ->getToken();

        $pdfUrl = $this->apiUrl()->setStoreId($this->getStoreId())->getPDF($draftHash);

        $postFields = [
            'jwt' => $JWT
        ];

        return $pdfUrl . '?' . http_build_query($postFields);
    }

    /**
     * @param string $userIdentifier
     * @param array  $drafts
     * @param bool   $dryRun
     *
     * @return array
     */
    public function migrateDrafts($userIdentifier, array $drafts, $dryRun = false)
    {
        $postFields = [
            'json' => [
                'user_identifier' => $userIdentifier,
                'drafts' => $drafts,
                'dryRun' => $dryRun
            ]
        ];

        return json_decode($this->getHttpClient()->post($this->apiUrl()->setStoreId($this->getStoreId())->getPrintformerBaseUrl() .
            '/api-ext/draft/claim', $postFields)->getBody(), true);
    }

    /**
     * @param string $userIdentifierOne
     * @param string $userIdentifierTwo
     *
     * @return array
     */
    public function userMerge($userIdentifierOne, $userIdentifierTwo)
    {
        $postFields = [
            'json' => [
                'source_user_identifier' => $userIdentifierTwo
            ]
        ];

        return json_decode($this->getHttpClient()->post($this->apiUrl()->setStoreId($this->getStoreId())->getPrintformerBaseUrl() . '/api-ext/user/' .
            $userIdentifierOne . '/merge', $postFields)->getBody(), true);
    }

    /**
     * @param string $draftHash
     * @param array  $dataParams
     *
     * @return mixed
     */
    public function updatePrintformerDraft($draftHash, $dataParams = [])
    {
        $url = $this->apiUrl()->setStoreId($this->getStoreId())->getDraft($draftHash);

        $response = $this->getHttpClient()->put($url, [
            'json' => $dataParams
        ]);

        if ($response->getStatusCode() == 200) {
            return true;
        }

        return false;
    }

    /**
     * @param string $draftHash
     * @param string $colorVariant
     *
     * @return bool
     */
    public function updateColorVariant($draftHash, $colorVariant)
    {
        if ($draftHash && $colorVariant) {
            $draftData = $this->getPrintformerDraft($draftHash);
            if ($draftData['variant']['version'] != $colorVariant) {
                return $this->updatePrintformerDraft($draftHash, [
                    'version' => $colorVariant
                ]);
            }
        }

        return false;
    }

    /**
     * @param $fileId
     *
     * @return string
     */
    public function getDerivateLink($fileId)
    {
        $JWTBuilder = (new Builder())
            ->setIssuedAt(time())
            ->set('client', $this->_config->setStoreId($this->getStoreId())->getClientIdentifier())
            ->setExpiration($this->_config->setStoreId($this->getStoreId())->getExpireDate());

        $JWT = (string)$JWTBuilder
            ->sign(new Sha256(), $this->_config->setStoreId($this->getStoreId())->getClientApiKey())
            ->getToken();

        $derivateDownloadLink = $this->apiUrl()->setStoreId($this->getStoreId())->getDerivat($fileId);

        $postFields = [
            'jwt' => $JWT
        ];

        return $derivateDownloadLink . '?' . http_build_query($postFields);
    }

    /**
     * @param $reviewId
     *
     * @return string
     */
    public function createReviewPdfUrl($reviewId)
    {
        $JWTBuilder = (new Builder())
            ->setIssuedAt(time())
            ->set('client', $this->_config->setStoreId($this->getStoreId())->getClientIdentifier())
            ->setExpiration($this->_config->setStoreId($this->getStoreId())->getExpireDate());

        $JWT = (string)$JWTBuilder
            ->sign(new Sha256(), $this->_config->setStoreId($this->getStoreId())->getClientApiKey())
            ->getToken();

        $createReviewPdfUrl = $this->apiUrl()->setStoreId($this->getStoreId())->createReviewPDF($reviewId);

        $postFields = [
            'jwt' => $JWT
        ];

        return $createReviewPdfUrl . '?' . http_build_query($postFields);
    }

    /**
     * @param $reviewId
     *
     * @return string
     */
    public function getReviewPdfUrl($reviewId)
    {
        $JWTBuilder = (new Builder())
            ->setIssuedAt(time())
            ->set('client', $this->_config->setStoreId($this->getStoreId())->getClientIdentifier())
            ->setExpiration($this->_config->setStoreId($this->getStoreId())->getExpireDate());

        $JWT = (string)$JWTBuilder
            ->sign(new Sha256(), $this->_config->setStoreId($this->getStoreId())->getClientApiKey())
            ->getToken();

        $createReviewPdfUrl = $this->apiUrl()->setStoreId($this->getStoreId())->getReviewPdf($reviewId);

        $postFields = [
            'jwt' => $JWT
        ];

        return $createReviewPdfUrl . '?' . http_build_query($postFields);
    }

    /**
     * @param $draftId
     *
     * @return string
     */
    public function getIdmlPackage($draftId)
    {
        $JWTBuilder = (new Builder())
            ->setIssuedAt(time())
            ->set('client', $this->_config->setStoreId($this->getStoreId())->getClientIdentifier())
            ->setExpiration($this->_config->setStoreId($this->getStoreId())->getExpireDate());

        $JWT = (string)$JWTBuilder
            ->sign(new Sha256(), $this->_config->setStoreId($this->getStoreId())->getClientApiKey())
            ->getToken();

        $getIdmlPackage = $this->apiUrl()->setStoreId($this->getStoreId())->getIdmlPackage($draftId);

        $postFields = [
            'jwt' => $JWT
        ];

        return $getIdmlPackage . '?' . http_build_query($postFields);
    }

    /**
     * @param $draftId
     *
     * @return string
     */
    public function closePagePlanner()
    {
        $JWTBuilder = (new Builder())
            ->setIssuedAt(time())
            ->set('client', $this->_config->setStoreId($this->getStoreId())->getClientIdentifier())
            ->setExpiration($this->_config->setStoreId($this->getStoreId())->getExpireDate());

        $JWT = (string)$JWTBuilder
            ->sign(new Sha256(), $this->_config->setStoreId($this->getStoreId())->getClientApiKey())
            ->getToken();

        $createReviewPdfUrl = $this->apiUrl()->setStoreId($this->getStoreId())->getPagePlannerApproveUrl();

        $postFields = [
            'jwt' => $JWT
        ];

        return $createReviewPdfUrl . '?' . http_build_query($postFields);
    }
}