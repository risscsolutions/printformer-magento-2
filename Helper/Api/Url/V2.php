<?php

namespace Rissc\Printformer\Helper\Api\Url;

use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Rissc\Printformer\Helper\Api\VersionInterface;
use Rissc\Printformer\Helper\Catalog as CatalogHelper;
use Rissc\Printformer\Helper\Config;

class V2 extends AbstractHelper implements VersionInterface
{
    const API_CREATE_USER = '/api-ext/user';
    const API_CREATE_DRAFT = '/api-ext/draft';
    const API_DELETE_DRAFT = '/api-ext/draft/{draftId}';
    const API_UPDATE_DRAFT = '/api-ext/draft/{draftId}';
    const API_GET_DRAFT_USAGE_PAGE_INFO = '/api-ext/draft/{draftId}/{usage}/page-info';
    const API_REPLICATE_DRAFT = '/api-ext/draft/{draftId}/replicate';
    const API_MERGE_USERS = '/api-ext/user/{user}/merge';
    const API_UPLOAD_DRAFT = '/api-ext/draft/{draftId}/upload';
    const API_DRAFT_PROCESSING = '/api-ext/pdf-processing';
    const API_URL_CALLBACKORDEREDSTATUS = 'printformer/api/callbackOrderedStatus';
    const API_GET_PRODUCTS = '/api-ext/template';

    const API_FILES_DRAFT_PNG = '/api-ext/files/draft/{draftId}/image';
    const API_FILES_DRAFT_PDF = '/api-ext/files/draft/{draftId}/print';
    const API_FILES_DRAFT_PREVIEW = '/api-ext/files/draft/{draftId}/low-res';
    const API_FILES_DERIVATE_FILE = '/api-ext/files/derivative/{fileId}/file';
    const API_FILE = '/api-ext/file';

    const EXT_EDITOR_PATH = '/editor';
    const EXT_AUTH_PATH = '/auth';

    const API_GET_USER = '/api-ext/user/{userId}';

    const API_CLIENT_NAME = '/api-ext/client';

    const API_PRODUCT_FEED = '/api-ext/product-feed';

    const API_GET_FEED_NAME = '/api-ext/product-feed/{feedIdentifier}';

    const API_CREATE_USER_GROUP = '/api-ext/user-group';


    /** Pageplanning START */
    const API_DRAFT_SETUP = '/api-ext/draft-setup';
    const API_EDITOR_VIEW = '/editor/{draftId}';

    const API_REVIEW_START = '/api-ext/review';
    const API_REVIEW_EDIT = '/review/{reviewId}/';
    const API_REVIEW_CREATE_REVIEW_PDF = '/api-ext/review/{reviewId}/create-review-pdf';
    const API_REVIEW_GET_REVIEW_PDF = '/api-ext/files/review/{reviewId}/pdf';
    const API_REVIEW_ADD_USER = '/api-ext/review/{reviewId}/add-user';
    const API_REVIEW_DELETE_USER = '/api-ext/review/{reviewId}/delete-user';

    const API_REQUEST_IDML_PACKAGE = '/api-ext/draft/{draftId}/request-idml-package';
    const API_GET_IDML_PACKAGE = '/api-ext/files/draft/{draftId}/idml-package';

    const API_PAGE_PLANNER_APPROVE = '/api-ext/page-planner/approve';
    const API_PAGE_PLANNER_DELETE = '/api-ext/page-planner/delete';
    /** Pageplanning END */

    /** @var StoreManagerInterface */
    protected $_storeManager;

    /** @var Config */
    protected $_config;

    /** @var CustomerSession */
    protected $_customerSession;

    protected $_storeId = 0;

    /** @var CatalogHelper */
    protected $_catalogHelper;

    /**
     * @var Configuration
     */
    private $jwtConfig;

    /**
     * V2 constructor.
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param CustomerSession $customerSession
     * @param CatalogHelper $catalogHelper
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Config $config,
        CustomerSession $customerSession,
        CatalogHelper $catalogHelper
    )
    {
        $this->_storeManager = $storeManager;
        $this->_config = $config;
        $this->_customerSession = $customerSession;
        $this->_catalogHelper = $catalogHelper;

        try {
            $storeId = $this->getStoreId();
            $apiKey = $this->_config->getClientApiKey($storeId);
            if (!empty($apiKey)) {
                $this->jwtConfig = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($apiKey));
            }
        } catch (NoSuchEntityException $e) {
        }

        parent::__construct($context);
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getEditorEntry(
        $productId,
        $identifier,
        $draftHash,
        $params = [],
        $intent = null,
        $user = null
    )
    {
        $baseParams = [
            'identifier' => $identifier,
            'product_id' => $productId,
            'intent' => $intent
        ];
        if ($draftHash !== null) {
            $baseParams = array_merge($baseParams, [
                'draft_id' => $draftHash
            ]);
        }

        if (!empty($params['quote_id'])) {
            $baseParams['quote_id'] = $params['quote_id'];
        }

        $baseUrl = $this->_urlBuilder->getUrl('printformer/editor/open', $baseParams);

        return $baseUrl . (!empty($params) ? '?' . http_build_query($params) : '');
    }

    /**
     * @return string
     */
    public function getPrintformerBaseUrl($storeId = false, $websiteId = false)
    {
        if ($storeId === false && $websiteId === false) {
            $storeId = $this->_storeManager->getStore()->getId();
            $websiteId = $this->_storeManager->getWebsite()->getId();
        }
        return $this->_config->getClientUrl($storeId, $websiteId);
    }

    /**
     * {@inheritdoc}
     */
    public function getUser()
    {
        return $this->getPrintformerBaseUrl() .
            self::API_CREATE_USER;
    }

    /**
     * {@inheritdoc}
     */
    public function getDraft(
        $draftHash = null,
        $quoteId = null
    )
    {
        $draftUrl = $this->getPrintformerBaseUrl() .
            self::API_CREATE_DRAFT;

        if ($draftHash) {
            return $draftUrl . '/' . $draftHash;
        }

        return $draftUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function getReplicateDraftId($oldDraftId)
    {
        return $this->getPrintformerBaseUrl() . str_replace('{draftId}', $oldDraftId, self::API_REPLICATE_DRAFT);
    }

    /**
     * {@inheritdoc}
     */
    public function getMergeUsers($originUserIdentifier)
    {
        return $this->getPrintformerBaseUrl() . str_replace('{user}', $originUserIdentifier, self::API_MERGE_USERS);
    }

    /**
     * @param $draftId
     * @return string
     */
    public function getUploadDraftId($draftId)
    {
        return $this->getPrintformerBaseUrl() . str_replace('{draftId}', $draftId, self::API_UPLOAD_DRAFT);
    }

    /**
     * {@inheritdoc}
     */
    public function getEditor(
        $draftHash,
        $user = null,
        $params = []
    )
    {
        $storeId = $this->_storeManager->getStore()->getId();
        // Check store param for admin pages
        if (isset($params['store_id'])){
            $storeId = $params['store_id'];
        }

        $editorUrl = $this->getPrintformerBaseUrl($storeId) .
            self::EXT_EDITOR_PATH;

        $dataParams = [
            'product_id' => $params['product_id'],
            'draft_process' => $params['data']['draft_process']
        ];

        if (!empty($params['data']['quote_id'])) {
            $dataParams['quote_id'] = $params['data']['quote_id'];
        }

        $customCallbackUrl = null;
        if (!empty($params['data']['callback_url'])) {
            $customCallbackUrl = $params['data']['callback_url'];
        }

        $queryParams = [];
        $queryParams['callback'] = $this->_getCallbackUrl(
            $customCallbackUrl,
            $storeId,
            $dataParams
        );

        if ($this->_config->getRedirectProductOnCancel()) {
            $queryParams['callback_cancel'] = $this->_getProductCallbackUrl(intval($params['product_id']), $params['data'], $storeId);
        }

        return $editorUrl . '/' . $draftHash . '?' . http_build_query($queryParams);
    }

    /**
     * {@inheritdoc}
     */
    public function getAuth()
    {
        return $this->getPrintformerBaseUrl() .
            self::EXT_AUTH_PATH;
    }

    /**
     * @param string $requestReferrer
     * @param int $storeId
     * @param array $params
     * @param bool $encodeUrl
     *
     * @return string
     */
    protected function _getCallbackUrl(
        $requestReferrer,
        $storeId = 0,
        $params = [],
        $encodeUrl = true
    )
    {
        if ($requestReferrer != null) {
            $referrer = urldecode($requestReferrer);
        } else {
            $referrerParams = array_merge($params, [
                'store_id' => $storeId,
            ]);

            if (isset($params['quote_id']) && isset($params['product_id'])) {
                $referrerParams['quote_id'] = $params['quote_id'];
                $referrerParams['edit_product'] = $params['product_id'];
                $referrerParams['is_edit'] = 1;
            }

            $referrer = $this->_urlBuilder->getUrl('printformer/editor/save', $referrerParams);
        }

        if ($encodeUrl) {
            $referrer = base64_encode($referrer);
        }

        return $referrer;
    }

    /**
     * @param Product | int $product
     * @param int $storeId
     * @param bool $encodeUrl
     *
     * @return string
     */
    protected function _getProductCallbackUrl(
        $product,
        $params = [],
        $storeId = 0,
        $encodeUrl = true
    )
    {
        $product = $this->_catalogHelper->prepareProduct($product);
         // Check store id for admin pages
        if ($storeId){
            $product->setStoreId($storeId);
        }
        if (isset($params['quote_id']) && $product->getId()) {
            $referrerParams['id'] = $params['quote_id'];
            $referrerParams['product_id'] = $product->getId();

            $baseUrl = $this->_urlBuilder->getUrl('checkout/cart/configure', $referrerParams);
        } else {
            $baseUrl = $product->getProductUrl(null);
        }

        if ($encodeUrl) {
            $baseUrl = base64_encode($baseUrl);
        }

        return $baseUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function getDraftProcessing(
        $draftHashes = [],
        $quoteId = null
    )
    {
        return $this->getPrintformerBaseUrl() .
            self::API_DRAFT_PROCESSING;
    }

    /**
     * {@inheritdoc}
     */
    public function getThumbnail($draftHash)
    {
        $draftHash = explode(',', $draftHash ?? '')[0];
        return $this->getPrintformerBaseUrl() . str_replace('{draftId}', $draftHash, self::API_FILES_DRAFT_PNG);
    }

    /**
     * {@inheritdoc}
     */
    public function getPDF(
        $draftHash,
        $quoteid = null,
        $storeId = false,
        $websiteId = false
    )
    {
        return $this->getPrintformerBaseUrl($storeId, $websiteId) .
            str_replace('{draftId}', $draftHash, self::API_FILES_DRAFT_PDF);
    }

    /**
     * {@inheritdoc}
     */
    public function getPreviewPDF(
        $draftHash,
        $quoteid = null,
        $storeId = false,
        $websiteId = false
    )
    {
        return $this->getPrintformerBaseUrl($storeId, $websiteId) .
            str_replace('{draftId}', $draftHash, self::API_FILES_DRAFT_PREVIEW);
    }

    /**
     * {@inheritdoc}
     */
    public function createReviewPDF($reviewId)
    {
        $replaceString = [
            '{reviewId}' => $reviewId
        ];
        return $this->getPrintformerBaseUrl() . strtr(self::API_REVIEW_CREATE_REVIEW_PDF, $replaceString);
    }

    public function getReviewPdf($reviewId)
    {
        $replaceString = [
            '{reviewId}' => $reviewId
        ];
        return $this->getPrintformerBaseUrl() . strtr(self::API_REVIEW_GET_REVIEW_PDF, $replaceString);
    }

    public function createIdmlPackage($draftId)
    {
        return $this->getPrintformerBaseUrl() .
            str_replace('{draftId}', $draftId, self::API_REQUEST_IDML_PACKAGE);
    }

    public function getIdmlPackage($draftId)
    {
        return $this->getPrintformerBaseUrl() .
            str_replace('{draftId}', $draftId, self::API_GET_IDML_PACKAGE);
    }

    /**
     * {@inheritdoc}
     */
    public function getProducts($storeId = false, $websiteId = false)
    {
        return $this->getPrintformerBaseUrl($storeId, $websiteId) .
            self::API_GET_PRODUCTS;
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminProducts($storeId = false, $websiteId = false)
    {
        return $this->getProducts($storeId, $websiteId);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminEditor(
        $draftHash,
        array $params = null,
        $referrer = null
    )
    {
        return $this->getEditor($draftHash, null, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminDraft(
        $draftHash,
        $quoteId
    )
    {
        return $this->getDraft($draftHash);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminPDF(
        $draftHash,
        $quoteId,
        $storeId = false
    )
    {
        $websiteId = false;
        if ($storeId) {
            $websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();
            $apiKey = $this->_config->getClientApiKey($storeId, $websiteId);
            if (!empty($apiKey)) {
                $this->jwtConfig = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($apiKey));
            }
        }
        $issuedAt = new DateTimeImmutable();
        $expirationDate = $this->_config->getExpireDate($storeId, $websiteId);
        $JWTBuilder = $this->jwtConfig->builder()
            ->issuedAt($issuedAt)
            ->withClaim('client', $this->_config->getClientIdentifier($storeId, $websiteId))
            ->expiresAt($expirationDate);
        $JWT = $JWTBuilder->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey())->toString();

        $pdfUrl = $this->getPDF($draftHash, $quoteId, $storeId, $websiteId);
        $postFields = [
            'jwt' => $JWT
        ];

        return $pdfUrl . '?' . http_build_query($postFields);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminPreviewPDF(
        $draftHash,
        $quoteId,
        $storeId = false
    )
    {
        $websiteId = false;
        if ($storeId) {
            $websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();
            $apiKey = $this->_config->getClientApiKey($storeId, $websiteId);
            if (!empty($apiKey)) {
                $this->jwtConfig = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($apiKey));
            }
        }

        $issuedAt = new DateTimeImmutable();
        $expirationDate = $this->_config->getExpireDate($storeId, $websiteId);
        $JWTBuilder = $this->jwtConfig->builder()
            ->issuedAt($issuedAt)
            ->withClaim('client', $this->_config->getClientIdentifier($storeId, $websiteId))
            ->expiresAt($expirationDate);
        $JWT = $JWTBuilder->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey())->toString();

        $pdfUrl = $this->getPreviewPDF($draftHash, $storeId, $websiteId);
        $postFields = [
            'jwt' => $JWT
        ];

        return $pdfUrl . '?' . http_build_query($postFields);
    }

    public function getReviewEditAuth(
        $reviewId,
        $userIdentifier,
        $callbackUrl
    )
    {
        $calbackUrls = [
            'redirect-url' => base64_encode($this->_storeManager->getStore()->getBaseUrl() . 'customer/account/'),
            'submit-callback-url' => base64_encode(rtrim($this->_storeManager->getStore()->getBaseUrl(), '/') . '/' . $callbackUrl)
        ];

        // temporary fix for getting the correct review edit url with printformer_base_url for correct storeview
        if (class_exists('\Mgo\WebService\Helper\ExternalReviewUser')) {
            $obj = ObjectManager::getInstance();
            $externalReviewUserHelper = $obj->create('\Mgo\WebService\Helper\ExternalReviewUser');
            $storeId = $externalReviewUserHelper->getStoreIdFromReviewByReviewHash($reviewId);
            $reviewEditUrl = $this->getReviewEditUrl($reviewId) . '?' . http_build_query($calbackUrls);
        } else {
            // not an mgo project
            $reviewEditUrl = $this->getReviewEditUrl($reviewId) . '?' . http_build_query($calbackUrls);
            $storeId = $this->_storeId;
        }
        $client = $this->_config->getClientIdentifier($storeId);
        $identifier = bin2hex(random_bytes(16));
        $issuedAt = new DateTimeImmutable();
        $expirationDate = $this->_config->getExpireDate();
        $JWTBuilder = $this->jwtConfig->builder()
            ->issuedAt($issuedAt)
            ->withClaim('client', $client)
            ->withClaim('user', $userIdentifier)
            ->identifiedBy($identifier)
            ->withClaim('redirect', $reviewEditUrl)
            ->expiresAt($expirationDate)
            ->withHeader('jti', $identifier);
        $JWT = $JWTBuilder->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey())->toString();

        $postFields = [
            'jwt' => $JWT
        ];

        return $this->getAuth() . '?' . http_build_query($postFields);
    }

    /**
     * {@inheritdoc}
     */
    public function getDraftDelete($draftHash)
    {
        return $this->getPrintformerBaseUrl() . str_replace('{draftId}', $draftHash, self::API_DELETE_DRAFT);
    }

    /**
     * {@inheritdoc}
     */
    public function getDraftUpdate($draftHash)
    {
        return $this->getPrintformerBaseUrl() . str_replace('{draftId}', $draftHash, self::API_UPDATE_DRAFT);
    }

    /**
     * {@inheritdoc}
     */
    public function getDraftUsagePageInfo(
        $draftHash,
        $usage
    )
    {
        return $this->getPrintformerBaseUrl() . str_replace(
            array('{draftId}', '{usage}'),
            array($draftHash, $usage),
            self::API_GET_DRAFT_USAGE_PAGE_INFO
        );
    }

    public function getRedirect(
        ProductInterface $product = null,
        array $redirectParams = null
    )
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getDerivat($fileId)
    {
        return $this->getPrintformerBaseUrl() .
            str_replace('{fileId}', $fileId, self::API_FILES_DERIVATE_FILE);
    }

    /**
     * @return string
     */
    public function getPagePlannerUrl()
    {
        return $this->getPrintformerBaseUrl() . self::API_DRAFT_SETUP;
    }

    public function getReviewStartUrl()
    {
        return $this->getPrintformerBaseUrl() . self::API_REVIEW_START;
    }

    public function getReviewEditUrl($reviewId)
    {
        $replaceString = [
            '{reviewId}' => $reviewId
        ];
        return $this->getPrintformerBaseUrl() . strtr(self::API_REVIEW_EDIT, $replaceString);
    }

    public function getPagePlannerApproveUrl()
    {
        return $this->getPrintformerBaseUrl() . self::API_PAGE_PLANNER_APPROVE;
    }

    public function getReviewUserAddUrl($reviewId)
    {
        $replaceString = [
            '{reviewId}' => $reviewId
        ];
        return $this->getPrintformerBaseUrl() . strtr(self::API_REVIEW_ADD_USER, $replaceString);
    }

    public function getReviewUserDeleteUrl($reviewId)
    {
        $replaceString = [
            '{reviewId}' => $reviewId
        ];
        return $this->getPrintformerBaseUrl() . strtr(self::API_REVIEW_DELETE_USER, $replaceString);
    }

    public function getPagePlannerDeleteUrl()
    {
        return $this->getPrintformerBaseUrl() . self::API_PAGE_PLANNER_DELETE;
    }

    public function getUserData($identifier)
    {
        return $this->getPrintformerBaseUrl() .
            str_replace('{userId}', $identifier, self::API_GET_USER);
    }

    /**
     * @return string
     */
    public function getUploadFileUrl()
    {
        return $this->getPrintformerBaseUrl() . self::API_FILE;
    }

    /**
     * @return string
     */
    public function getProductFeedUrl()
    {
        return $this->getPrintformerBaseUrl() . self::API_PRODUCT_FEED;
    }

    public function getProductFeedName($feedIdentifier)
    {
        return $this->getPrintformerBaseUrl() .
            str_replace('{feedIdentifier}', $feedIdentifier, self::API_GET_FEED_NAME);
    }

    /**
     * @return string
     */
    public function getClientName($storeId = false, $websiteId = false)
    {
        return $this->getPrintformerBaseUrl($storeId, $websiteId) . self::API_CLIENT_NAME;
    }

    /**
     * @return string
     */
    public function createUserGroupUrl(): string
    {
        return $this->getPrintformerBaseUrl() . self::API_CREATE_USER_GROUP;
    }
}
