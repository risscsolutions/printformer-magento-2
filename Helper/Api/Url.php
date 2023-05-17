<?php
namespace Rissc\Printformer\Helper\Api;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Rissc\Printformer\Helper\Api\Url\V2 as V2Helper;
use Rissc\Printformer\Helper\Config;
use Rissc\Printformer\Model\Config\Source\Redirect;

class Url extends AbstractHelper implements VersionInterface
{
    const API_URL_CALLBACKORDEREDSTATUS = 'printformer/api/callbackOrderedStatus';

    /** @var V2Helper */
    protected $_versionHelper = null;

    /** @var StoreManagerInterface */
    protected $_storeManager;

    /** @var Config  */
    protected $config;

    protected $_storeId = 0;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Config $config
    ) {
        $this->_storeManager = $storeManager;
        $this->config = $config;

        parent::__construct($context);
    }

    /**
     * @return StoreManagerInterface
     */
    public function getStoreManager()
    {
        return $this->_storeManager;
    }

    /**
     * @param $storeManager
     *
     * @return $this
     */
    public function setStoreManager($storeManager)
    {
        $this->_storeManager = $storeManager;

        return $this;
    }

    /**
     * @param bool $isV2Api
     *
     * @return $this
     */
    public function initVersionHelper()
    {
        $objm = ObjectManager::getInstance();
        $this->_versionHelper = $objm->create(V2Helper::class);

        return $this;
    }

    public function getVersionHelper()
    {
        if (!$this->_versionHelper) {
            $this->initVersionHelper();
        }

        return $this->_versionHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getEditorEntry($productId, $masterId, $draftHash, $params = [], $intent = null, $user = null)
    {
        return $this->getVersionHelper()->getEditorEntry($productId, $masterId, $draftHash, $params, $intent, $user);
    }

    /**
     * {@inheritdoc}
     */
    public function getPrintformerBaseUrl($storeId = false, $websiteId = false)
    {
        return $this->getVersionHelper()->getPrintformerBaseUrl($storeId, $websiteId);
    }

    /**
     * {@inheritdoc}
     */
    public function getUser()
    {
        return $this->getVersionHelper()->getUser();
    }

    /**
     * {@inheritdoc}
     */
    public function getDraft($draftHash = null, $quoteId = null)
    {
        return $this->getVersionHelper()->getDraft($draftHash, $quoteId);
    }

    /**
     * {@inheritdoc}
     */
    public function getEditor($draftHash, $user = null, $params = [])
    {
        return $this->getVersionHelper()->getEditor($draftHash, $user, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function getAuth()
    {
        return $this->getVersionHelper()->getAuth();
    }

    /**
     * {@inheritdoc}
     */
    public function getDraftProcessing($draftHashes = [], $quoteId = null)
    {
        return $this->getVersionHelper()->getDraftProcessing($draftHashes, $quoteId);
    }

    /**
     * @param $url
     * @return string
     */
    public function appendUniqueGetParam($url)
    {
        $permittedChars = '0123456789abcdefghijklmnopqrstuvwxyz';
        $randomGetParam = substr(str_shuffle($permittedChars), 0, 20);
        $url = $url.'?CacheId='.$randomGetParam;
        return $url;
    }

    /**
     * @param string $draftHash
     * @param int $uniqueGetParam
     * @return string
     */
    public function getThumbnail($draftHash, $uniqueGetParam = 1)
    {
        $thumbnailUrl = $this->getVersionHelper()->getThumbnail($draftHash);
        if ($uniqueGetParam) {
            $thumbnailUrl = $this->appendUniqueGetParam($thumbnailUrl);
        }
        return $thumbnailUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function getPDF($draftHash, $quoteId = null, $storeId = false, $websiteId = false)
    {
        return $this->getVersionHelper()->getPDF($draftHash, $quoteId, $storeId, $websiteId);
    }

    /**
     * {@inheritdoc}
     */
    public function getPreviewPDF($draftHash, $quoteId = null, $storeId = false, $websiteId = false)
    {
        return $this->getVersionHelper()->getPreviewPDF($draftHash, $quoteId, $storeId, $websiteId);
    }

    /**
     * {@inheritdoc}
     */
    public function getProducts()
    {
        return $this->getVersionHelper()->getProducts();
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminProducts($storeId = false, $websiteId = false)
    {
        return $this->getVersionHelper()->getAdminProducts($storeId, $websiteId);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminPDF($draftHash, $quoteId, $storeId)
    {
        return $this->getVersionHelper()->getAdminPDF($draftHash, $quoteId, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminPreviewPDF($draftHash, $quoteId, $storeId)
    {
        return $this->getVersionHelper()->getAdminPreviewPDF($draftHash, $quoteId, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminEditor($draftHash, array $params = null, $referrer = null)
    {
        return $this->getVersionHelper()->getAdminEditor($draftHash, $params, $referrer);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminDraft($draftHash, $quoteId)
    {
        return $this->getVersionHelper()->getAdminDraft($draftHash, $quoteId);
    }

    /**
     * {@inheritdoc}
     */
    public function getDraftDelete($draftHash)
    {
        return $this->getVersionHelper()->getDraftDelete($draftHash);
    }

    /**
     * {@inheritdoc}
     */
    public function getDraftUpdate($draftHash)
    {
        return $this->getVersionHelper()->getDraftUpdate($draftHash);
    }

    /**
     * {@inheritdoc}
     */
    public function getDraftUsagePageInfo($draftHash, $pageInfo)
    {
        return $this->getVersionHelper()->getDraftUsagePageInfo($draftHash, $pageInfo);
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirect(ProductInterface $product = null, array $redirectParams = null)
    {
        if (!$redirectParams) {
            switch ($this->config->getConfigRedirect()) {
                case Redirect::CONFIG_REDIRECT_URL_ALT:
                    return $this->config->getRedirectAlt();
                case Redirect::CONFIG_REDIRECT_URL_CART:
                    return $this->_urlBuilder->getUrl('checkout/cart', ['_use_rewrite' => true]);
                case Redirect::CONFIG_REDIRECT_URL_PRODUCT:
                default:
                    return $product->getUrlModel()->getUrl($product);
            }
        }

        return $this->_urlBuilder->getUrl($redirectParams['controller'], $redirectParams['params']);
    }

    /**
     * {@inheritdoc}
     */
    public function getReplicateDraftId($oldDraftId)
    {
        return $this->getVersionHelper()->getReplicateDraftId($oldDraftId);
    }

    /**
     * {@inheritdoc}
     */
    public function getMergeUser($originUserIdentifier)
    {
        return $this->getVersionHelper()->getMergeUsers($originUserIdentifier);
    }

    /**
     * {@inheritdoc}
     */
    public function getUploadDraftId($draftId)
    {
        return $this->getVersionHelper()->getUploadDraftId($draftId);
    }

    /**
     * {@inheritdoc}
     */
    public function getDerivat($fileId)
    {
        return $this->getVersionHelper()->getDerivat($fileId);
    }

    public function createReviewPDF($reviewId)
    {
        return $this->getVersionHelper()->createReviewPDF($reviewId);
    }

    public function getReviewPdf($reviewId)
    {
        return $this->getVersionHelper()->getReviewPdf($reviewId);
    }

    public function getPagePlannerUrl()
    {
        return $this->getVersionHelper()->getPagePlannerUrl();
    }

    public function getReviewStartUrl()
    {
        return $this->getVersionHelper()->getReviewStartUrl();
    }

    public function getReviewEditUrl($reviewId)
    {
        return $this->getVersionHelper()->getReviewEditUrl($reviewId);
    }

    public function getReviewEditAuth($reviewId, $userIdentifier, $callbackUrl)
    {
        return $this->getVersionHelper()->getReviewEditAuth($reviewId, $userIdentifier, $callbackUrl);
    }

    public function createIdmlPackage($draftId)
    {
        return $this->getVersionHelper()->createIdmlPackage($draftId);
    }

    public function getIdmlPackage($draftId)
    {
        return $this->getVersionHelper()->getIdmlPackage($draftId);
    }

    public function getPagePlannerApproveUrl()
    {
        return $this->getVersionHelper()->getPagePlannerApproveUrl();
    }

    public function getReviewUserAddUrl($reviewId)
    {
        return $this->getVersionHelper()->getReviewUserAddUrl($reviewId);
    }

    public function getReviewUserDeleteUrl($reviewId)
    {
        return $this->getVersionHelper()->getReviewUserDeleteUrl($reviewId);
    }

    public function getPagePlannerDeleteUrl()
    {
        return $this->getVersionHelper()->getPagePlannerDeleteUrl();
    }

    public function getUserData($identifier)
    {
        return $this->getVersionHelper()->getUserData($identifier);
    }

    public function getUploadFileUrl()
    {
        return $this->getVersionHelper()->getUploadFileUrl();
    }

    public function getProductFeedUrl()
    {
        return $this->getVersionHelper()->getProductFeedUrl();
    }

    public function getClientName($storeId = false, $websiteId = false)
    {
        return $this->getVersionHelper()->getClientName($storeId, $websiteId);
    }
}
