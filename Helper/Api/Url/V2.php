<?php
namespace Rissc\Printformer\Helper\Api\Url;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use Rissc\Printformer\Gateway\Admin\Draft;
use Rissc\Printformer\Helper\Api\VersionInterface;
use Magento\Store\Model\StoreManagerInterface;
use Rissc\Printformer\Helper\Config;

class V2
    extends AbstractHelper
    implements VersionInterface
{
    const API_CREATE_USER               = '/api-ext/user';
    const API_CREATE_DRAFT              = '/api-ext/draft';
    const API_DRAFT_PROCESSING          = '/api-ext/pdf-processing';
    const API_URL_CALLBACKORDEREDSTATUS = 'printformer/api/callbackOrderedStatus';
    const API_GET_PRODUCTS              = '/api-ext/template';

    const API_FILES_DRAFT_PNG           = '/api-ext/files/draft/{draftId}/image';
    const API_FILES_DRAFT_PDF           = '/api-ext/files/draft/{draftId}/print';

    const EXT_EDITOR_PATH               = '/editor';
    const EXT_AUTH_PATH                 = '/auth';

    /** @var StoreManagerInterface */
    protected $_storeManager;

    /** @var Config */
    protected $_config;

    /**
     * V2 constructor.
     *
     * @param Context               $context
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Config $config
    )
    {
        $this->_storeManager = $storeManager;
        $this->_config = $config;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function setStoreId($storeId)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getStoreId()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getEditorEntry($productId, $masterId, $draftHash, $params = [], $intent = null, $user = null)
    {
        $baseParams = [
            'master_id' => $masterId,
            'product_id' => $productId,
            'intent' => $intent
        ];
        if($draftHash !== null) {
            $baseParams = array_merge($baseParams, [
                'draft_id' => $draftHash
            ]);
        }

        $baseUrl = $this->_urlBuilder->getUrl('printformer/editor/open', $baseParams);

        return $baseUrl . (!empty($params) ? '?' . http_build_query($params) : '');
    }

    /**
     * {@inheritdoc}
     */
    public function getPrintformerBaseUrl()
    {
        $store = $this->_storeManager->getStore();
        return $this->scopeConfig->getValue('printformer/version2group/v2url',
            ScopeInterface::SCOPE_STORES, $store->getId());
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
    public function getDraft($draftHash = null, $quoteId = null)
    {
        $draftUrl = $this->getPrintformerBaseUrl() .
            self::API_CREATE_DRAFT;

        if($draftHash) {
            return $draftUrl . '/' . $draftHash;
        }

        return $draftUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function getEditor($draftHash, $user = null, $params = [])
    {
        $editorUrl = $this->getPrintformerBaseUrl() .
            self::EXT_EDITOR_PATH;

        $dataParams = [
            'product_id' => $params['product_id'],
            'draft_process' => $params['data']['draft_process']
        ];

        $customCallbackUrl = null;
        if(!empty($params['data']['callback_url'])) {
            $customCallbackUrl = $params['data']['callback_url'];
        }

        $queryParams = [];
        $queryParams['callback'] = $this->_getCallbackUrl($customCallbackUrl, $this->_storeManager->getStore()->getId(),
            $dataParams);

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
     * @param int    $storeId
     * @param array  $params
     * @param bool   $encodeUrl
     *
     * @return string
     */
    protected function _getCallbackUrl($requestReferrer, $storeId = 0, $params = [], $encodeUrl = true)
    {
        if($requestReferrer != null) {
            $referrer = urldecode($requestReferrer);
        } else {
            $referrerParams = array_merge($params, [
                'store_id'      => $storeId,
            ]);

            if(isset($params['quote_id']) && isset($params['product_id'])) {
                $referrerParams['quote_id'] = $params['quote_id'];
                $referrerParams['edit_product'] = $params['product_id'];
                $referrerParams['is_edit'] = 1;
            }

            $referrer = $this->_urlBuilder->getUrl('printformer/editor/save', $referrerParams);
        }

        if($encodeUrl) {
            $referrer = base64_encode($referrer);
        }

        return $referrer;
    }

    /**
     * {@inheritdoc}
     */
    public function getDraftProcessing($draftHashes = [], $quoteId = null)
    {
        return $this->getPrintformerBaseUrl() .
            self::API_DRAFT_PROCESSING;
    }

    /**
     * {@inheritdoc}
     */
    public function getThumbnail($draftHash) {
        return $this->getPrintformerBaseUrl() .
            str_replace('{draftId}', $draftHash, self::API_FILES_DRAFT_PNG);
    }

    /**
     * {@inheritdoc}
     */
    public function getPDF($draftHash, $quoteid = null) {
        return $this->getPrintformerBaseUrl() .
            str_replace('{draftId}', $draftHash, self::API_FILES_DRAFT_PDF);
    }

    /**
     * {@inheritdoc}
     */
    public function getProducts()
    {
        return $this->getPrintformerBaseUrl() .
            self::API_GET_PRODUCTS;
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminProducts()
    {
        return $this->getProducts();
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminEditor($draftHash, array $params = null, $referrer = null)
    {
        return $this->getEditor($draftHash, null, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminDraft($draftHash, $quoteId)
    {
        return $this->getDraft($draftHash);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminPDF($draftHash, $quoteId)
    {
        return $this->getPDF($draftHash);
    }

    /**
     * {@inheritdoc}
     */
    public function getDraftDelete($draftHash)
    {
        // TODO: Implement getDraftDelete() method.
    }

    public function getRedirect(ProductInterface $product = null, array $redirectParams = null)
    {
        return '';
    }
}