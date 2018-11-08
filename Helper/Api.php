<?php
namespace Rissc\Printformer\Helper;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use GuzzleHttp\Client;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Helper\Context;
use Rissc\Printformer\Helper\Api\Url as UrlHelper;
use Magento\Store\Model\StoreManagerInterface;
use Rissc\Printformer\Model\Draft;
use Rissc\Printformer\Model\DraftFactory;
use GuzzleHttp\Psr7\Stream as Psr7Stream;
use Rissc\Printformer\Helper\Session as SessionHelper;
use DateTime;
use DateInterval;

class Api
    extends AbstractHelper
{
    const API_URL_CALLBACKORDEREDSTATUS = 'callbackOrderedStatus';

    /** @var UrlHelper */
    protected $_urlHelper;

    /** @var Client */
    protected $_httpClient;

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

    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        UrlHelper $urlHelper,
        StoreManagerInterface $storeManager,
        DraftFactory $draftFactory,
        SessionHelper $sessionHelper,
        Config $config
    )
    {
        $this->_customerSession = $customerSession;
        $this->_urlHelper = $urlHelper;
        $this->_storeManager = $storeManager;
        $this->_draftFactory = $draftFactory;
        $this->_sessionHelper = $sessionHelper;
        $this->_config = $config;

        $this->apiUrl()->initVersionHelper($this->_config->isV2Enabled());
        $this->apiUrl()->setStoreManager($this->_storeManager);

        parent::__construct($context);

        $this->_httpClient = new Client([
            'base_url' => $this->apiUrl()->getPrintformerBaseUrl(),
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->_config->getClientApiKey(),
            ]
        ]);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getUserIdentifier()
    {
        if (!$this->_config->isV2Enabled()) {
            return null;
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
                $this->_customerSession->setPrintformerIdentification($customerUserIdentifier);
            } else {
                if ($customer->getData('printformer_identification') !=
                    $this->_customerSession->getPrintformerIdentification()) {
                    $this->_customerSession->setPrintformerIdentification(
                        $customer->getData('printformer_identification')
                    );
                }
            }
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
     * @param string $oldDraftId
     * @return string
     */
    public function getReplicateDraftId(string $oldDraftId) : string
    {
        $url = $this->apiUrl()->getReplicateDraftId($oldDraftId);

        $response = $this->_httpClient->get($url);
        $draftInfo = json_decode($response->getBody(), true);

        $draftHash = $draftInfo['data']['draftHash'];

        return $draftHash;
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
        $editorOpenUrl = $this->apiUrl()->getEditor($draftHash, null, $params);

        $JWTBuilder = (new Builder())
            ->setIssuedAt(time())
            ->set('client', $this->_config->getClientIdentifier())
            ->set('user', $userIdentifier)
            ->setId(bin2hex(random_bytes(16)), true)
            ->set('redirect', $editorOpenUrl)
            ->setExpiration((new DateTime())->add(DateInterval::createFromDateString('+2 days'))->getTimestamp());

        $JWT = (string)$JWTBuilder
            ->sign(new Sha256(), $this->_config->getClientApiKey())
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
        $printformerProductId = null
    ) {
        $store = $this->_storeManager->getStore();

        $process = $this->getDraftProcess($draftHash, $productId, $intent, $sessionUniqueId);
        if(!$process->getId()) {
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
        $draftProcessingUrl = $this->apiUrl()->setStoreId($this->_storeManager->getStore()->getId())->getDraftProcessing($draftIds);
        $stateChangedNotifyUrl = $this->_urlBuilder->getUrl('rest/V1/printformer') . self::API_URL_CALLBACKORDEREDSTATUS;

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
                $process = $this->getDraftProcess($draftHash);
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
     * @param string $draftHash
     * @param string $userIdentifier
     * @param int $width
     * @param int $height
     * @param int $page
     * @return array
     */
    public function getThumbnail($draftHash, $userIdentifier, $width, $height, $page = 1)
    {
        $JWTBuilder = (new Builder())
            ->setIssuedAt(time())
            ->set('client', $this->_config->getClientIdentifier())
            ->set('user', $userIdentifier)
            ->setExpiration((new DateTime())->add(DateInterval::createFromDateString('+2 days'))->getTimestamp());

        $JWT = (string)$JWTBuilder
            ->sign(new Sha256(), $this->_config->getClientApiKey())
            ->getToken();

        $thumbnailUrl = $this->apiUrl()->getThumbnail($draftHash);

        $postFields = [
            'json' => [
                'jwt' => $JWT,
                'width' => $width,
                'height' => $height,
                'page' => $page
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
            ->set('client', $this->_config->getClientIdentifier())
            ->setExpiration((new DateTime())->add(DateInterval::createFromDateString('+2 days'))->getTimestamp());

        $JWT = (string)$JWTBuilder
            ->sign(new Sha256(), $this->_config->getClientApiKey())
            ->getToken();

        $pdfUrl = $this->apiUrl()->getPDF($draftHash);

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
     * @return string
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

        return json_encode($this->_httpClient->get($this->apiUrl()->getPrintformerBaseUrl() .
                '/api-ext/draft/claim', $postFields)->getBody());
    }

    /**
     * @param string $userIdentifierOne
     * @param string $userIdentifierTwo
     *
     * @return string
     */
    public function userMerge($userIdentifierOne, $userIdentifierTwo)
    {
        $postFields = [
            'json' => [
                'source_user_identifier' => $userIdentifierTwo
            ]
        ];

        return json_encode($this->_httpClient->get($this->apiUrl()->getPrintformerBaseUrl() . 'api-ext/user/' .
                $userIdentifierOne . '/merge', $postFields)->getBody());
    }

    /**
     * @param string $draftHash
     * @param array  $dataParams
     *
     * @return mixed
     */
    public function updatePrintformerDraft($draftHash, $dataParams = [])
    {
        $url = $this->apiUrl()->getDraft($draftHash);

        $response = $this->_httpClient->put($url, [
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
            ->set('client', $this->_config->getClientIdentifier())
            ->setExpiration((new DateTime())->add(DateInterval::createFromDateString('+2 days'))->getTimestamp());

        $JWT = (string)$JWTBuilder
            ->sign(new Sha256(), $this->_config->getClientApiKey())
            ->getToken();

        $derivateDownloadLink = $this->apiUrl()->getDerivat($fileId);

        $postFields = [
            'jwt' => $JWT
        ];

        return $derivateDownloadLink . '?' . http_build_query($postFields);
    }
}