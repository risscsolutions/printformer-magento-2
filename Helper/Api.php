<?php
namespace Rissc\Printformer\Helper;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use GuzzleHttp\Client;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Rissc\Printformer\Helper\Api\Url as UrlHelper;
use Magento\Store\Model\StoreManagerInterface;
use Rissc\Printformer\Model\Draft;
use Rissc\Printformer\Model\DraftFactory;
use Rissc\Printformer\Model\ResourceModel\Draft\Collection;
use GuzzleHttp\Psr7\Stream as Psr7Stream;

use DateTime;
use DateInterval;

class Api
    extends AbstractHelper
{
    /** @var Url */
    protected $_urlHelper;

    /** @var Client */
    protected $_httpClient;

    /** @var CustomerSession */
    protected $_customerSession;

    /** @var StoreManagerInterface */
    protected $_storeManager;

    /** @var DraftFactory */
    protected $_draftFactory;

    /** @var string */
    private $_clientApiKey = null;

    /** @var string */
    private $_clientIdentifier = null;

    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        UrlHelper $urlHelper,
        StoreManagerInterface $storeManager,
        DraftFactory $draftFactory
    )
    {
        $this->_customerSession = $customerSession;
        $this->_urlHelper = $urlHelper;
        $this->_storeManager = $storeManager;
        $this->_draftFactory = $draftFactory;

        $this->apiUrl()->setStoreManager($this->_storeManager);

        parent::__construct($context);

        $this->_httpClient = new Client([
            'base_url' => $this->apiUrl()->getPrintformerBaseUrl(),
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getClientApiKey(),
            ]
        ]);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getUserIdentifier()
    {
        $userIdentifier = $this->_customerSession->getPrintformerUserIdentifier();
        if(!$userIdentifier) {
            $userIdentifier = $this->createUser();
            $this->_customerSession->setPrintformerUserIdentifier($userIdentifier);

            if ($this->_customerSession->isLoggedIn()) {
                $customer = $this->_customerSession->getCustomer();
                $customer->setData('printformer_user_identifier', $userIdentifier);
                $customer->getResource()->save($customer);
            }
        }

        return $userIdentifier;
    }

    /**
     * @return string
     */
    private function getClientApiKey()
    {
        if(!$this->_clientApiKey) {
            $this->_clientApiKey = $this->scopeConfig->getValue(
                'printformer/version2group/v2apiKey',
                ScopeInterface::SCOPE_STORES
            );
        }

        return $this->_clientApiKey;
    }

    /**
     * @return string
     */
    private function getClientIdentifier()
    {
        if(!$this->_clientIdentifier) {
            $this->_clientIdentifier = $this->scopeConfig->getValue(
                'printformer/version2group/v2identifier',
                ScopeInterface::SCOPE_STORES
            );
        }

        return $this->_clientIdentifier;
    }

    /**
     * @return UrlHelper
     */
    public function apiUrl()
    {
        return $this->_urlHelper;
    }

    /**
     * @return string
     */
    public function createUser()
    {
        $url = $this->apiUrl()->getUser();

        $response = $this->_httpClient->post($url);
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
        $url = $this->apiUrl()->getDraft();

        $options = [
            'json' => [
                'master_id' => $masterId,
                'user_identifier' => $userIdentifier
            ]
        ];

        foreach($params as $key => $value) {
            $options['json'][$key] = $value;
        }

        $response = $this->_httpClient->post($url, $options);
        $response = json_decode($response->getBody(), true);

        return $response['data']['draftHash'];
    }

    /**
     * @param $draftHash
     *
     * @return mixed
     */
    public function getPrintformerDraft($draftHash)
    {
        $url = $this->apiUrl()->getDraft($draftHash);

        $response = $this->_httpClient->get($url);
        $response = json_decode($response->getBody(), true);

        return $response['data'];
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
        $editorOpenUrl = $this->apiUrl()->getEditor($draftHash, $params);

        $JWTBuilder = (new Builder())
            ->setIssuedAt(time())
            ->set('client', $this->getClientIdentifier())
            ->set('user', $userIdentifier)
            ->setId(bin2hex(random_bytes(16)), true)
            ->set('redirect', $editorOpenUrl)
            ->setExpiration((new DateTime())->add(DateInterval::createFromDateString('+2 days'))->getTimestamp());

        $JWT = (string)$JWTBuilder
            ->sign(new Sha256(), $this->getClientApiKey())
            ->getToken();

        return $this->apiUrl()->getAuth() . '?' . http_build_query(['jwt' => $JWT]);
    }

    /**
     * @param null $userIdentifier
     * @param null $draftHash
     * @param null $masterId
     * @param null $productId
     *
     * @return Draft
     * @throws \Exception
     */
    public function draftProcess($userIdentifier = null, $draftHash = null, $masterId = null, $productId = null)
    {
        $store = $this->_storeManager->getStore();

        $process = $this->getDraftProcess($userIdentifier, $draftHash, $masterId, $productId);
        if(!$process->getId()) {
            $process->addData([
                'user_identifier' => $userIdentifier,
                'draft_hash' => $draftHash,
                'master_id' => $masterId,
                'product_id' => $productId,
                'store_id' => $store->getId(),
                'created_at' => time()
            ]);
            $process->getResource()->save($process);
        }

        return $process;
    }

    /**
     * @param string $userIdentifier
     * @param string $draftHash
     * @param int    $masterId
     * @param int    $productId
     *
     * @return Draft
     */
    protected function getDraftProcess($userIdentifier = null, $draftHash = null, $masterId = null, $productId = null)
    {
        /** @var Draft $process */
        $process = $this->_draftFactory->create();
        /** @var Collection $processCollection */
        $processCollection = $process->getCollection();
        if(!$draftHash) {
            $processCollection->addFieldToFilter(
                Draft::KEY_USER_IDENTIFIER,
                ['eq' => $userIdentifier]
            );
            $processCollection->addFieldToFilter(
                Draft::KEY_MASTER_ID,
                ['eq' => $masterId]
            );
            $processCollection->addFieldToFilter(
                Draft::KEY_PRODUCT_ID,
                ['eq' => $productId]
            );
        } else if($draftHash !== null) {
            $processCollection->addFieldToFilter(
                Draft::KEY_DRAFT_HASH,
                ['eq' => $draftHash]
            );
        }

        if($processCollection->count() > 0) {
            $process = $processCollection->getFirstItem();
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
        $draftProcessingUrl = $this->apiUrl()->getDraftProcessingUrl();
        $stateChangedNotifyUrl = $this->_urlBuilder->getUrl(UrlHelper::API_URL_CALLBACKORDEREDSTATUS);

        $postFields = [
            'json' => [
                'draftIds' => $draftIds,
                'stateChangedNotifyUrl' => $stateChangedNotifyUrl
            ]
        ];

        $response = $this->_httpClient->post($draftProcessingUrl, $postFields);

        $responseArray = json_decode($response->getBody(), true);
        $processingHash = !empty($responseArray['processingId']) ? $responseArray['processingId'] : null;

        if($processingHash !== null) {
            foreach ($draftIds as $draftHash) {
                /** @var Draft $process */
                $process = $this->getDraftProcess(null, $draftHash);
                if ($process->getId()) {
                    $process->setProcessingId($processingHash);
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
     * @param $draftHash
     * @param $userIdentifier
     *
     * @return array
     */
    public function getThumbnail($draftHash, $userIdentifier)
    {
        $JWTBuilder = (new Builder())
            ->setIssuedAt(time())
            ->set('client', $this->getClientIdentifier())
            ->set('user', $userIdentifier)
            ->setExpiration((new DateTime())->add(DateInterval::createFromDateString('+2 days'))->getTimestamp());

        $JWT = (string)$JWTBuilder
            ->sign(new Sha256(), $this->getClientApiKey())
            ->getToken();

        $thumbnailUrl = $this->apiUrl()->getThumbnailUrl($draftHash);

        $postFields = [
            'json' => [
                'jwt' => $JWT,
                'width' => 200,
                'height' => 200
            ]
        ];

        $response = $this->_httpClient->get($thumbnailUrl, $postFields);

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
            ->set('client', $this->getClientIdentifier())
            ->set('user', $this->getUserIdentifier())
            ->setExpiration((new DateTime())->add(DateInterval::createFromDateString('+2 days'))->getTimestamp());

        $JWT = (string)$JWTBuilder
            ->sign(new Sha256(), $this->getClientApiKey())
            ->getToken();

        $pdfUrl = $this->apiUrl()->getPDFUrl($draftHash);

        $postFields = [
            'jwt' => $JWT
        ];

        return $pdfUrl . '?' . http_build_query($postFields);
    }
}