<?php
namespace Rissc\Printformer\Helper\Api\Url;

use Magento\Store\Model\ScopeInterface;
use Rissc\Printformer\Helper\Api\Url;
use Rissc\Printformer\Helper\Api\VersionInterface;

class V2
    extends Url
    implements VersionInterface
{
    const API_CREATE_USER               = '/api-ext/user';
    const API_CREATE_DRAFT              = '/api-ext/draft';
    const API_DRAFT_PROCESSING          = '/api-ext/pdf-processing';
    const API_URL_CALLBACKORDEREDSTATUS = 'printformer/api/callbackOrderedStatus';

    const API_FILES_DRAFT_PNG           = '/api-ext/files/draft/{draftId}/image';
    const API_FILES_DRAFT_PDF           = '/api-ext/files/draft/{draftId}/print';

    const EXT_EDITOR_PATH               = '/editor';
    const EXT_AUTH_PATH                 = '/auth';

    /**
     * {@inheritdoc}
     */
    public function getEditorEntry($productId, $masterId, $draftHash, $params = [], $intent = null, $user = null)
    {
        $baseParams = [
            'master_id' => $masterId,
            'product_id' => $productId
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
        if(!$this->_printformerBaseUrl) {
            $store = $this->_storeManager->getStore();
            $this->_printformerBaseUrl = $this->scopeConfig->getValue('printformer/version2group/v2url',
                ScopeInterface::SCOPE_STORES, $store->getId
                ());
        }

        return $this->_printformerBaseUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function getUser()
    {
        if(!$this->_userCreateUrl) {
            $this->_userCreateUrl = $this->getPrintformerBaseUrl() .
                self::API_CREATE_USER;
        }

        return $this->_userCreateUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function getDraft($draftHash = null)
    {
        if(!$this->_createDraftUrl) {
            $this->_createDraftUrl = $this->getPrintformerBaseUrl() .
                self::API_CREATE_DRAFT;
        }

        if($draftHash) {
            return $this->_createDraftUrl . '/' . $draftHash;
        }

        return $this->_createDraftUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function getEditor($draftHash, $params = [])
    {
        if(!$this->_editorUrl) {
            $this->_editorUrl = $this->getPrintformerBaseUrl() .
                self::EXT_EDITOR_PATH;
        }

        $dataParams = [
            'draft_process' => $params['data']['draft_process']
        ];
        /*
        foreach($dataParams as $key => $value) {
            $params[$key] = $value;
        }
        unset($params['data']);
        */

        $customCallbackUrl = null;
        if(isset($params['data']['callback_url'])) {
            $customCallbackUrl = $params['data']['callback_url'];
        }

        $queryParams = [];
        $queryParams['callback'] = $this->_getCallbackUrl($customCallbackUrl, $this->_storeManager->getStore()->getId(),
            $dataParams);

        return $this->_editorUrl . '/' . $draftHash . '?' . http_build_query($queryParams);
    }

    /**
     * {@inheritdoc}
     */
    public function getAuth()
    {
        if(!$this->_authUrl) {
            $this->_authUrl = $this->getPrintformerBaseUrl() .
                self::EXT_AUTH_PATH;
        }

        return $this->_authUrl;
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
        if(!$this->_draftProcessingUrl) {
            $this->_draftProcessingUrl = $this->getPrintformerBaseUrl() .
                self::API_DRAFT_PROCESSING;
        }

        return $this->_draftProcessingUrl;
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
    public function getPDF($draftHash) {
        return $this->getPrintformerBaseUrl() .
            str_replace('{draftId}', $draftHash, self::API_FILES_DRAFT_PDF);
    }

    /**
     * {@inheritdoc}
     */
    public function getProducts()
    {
        // TODO: Implement getAdminProducts() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminProducts()
    {
        return $this->getProducts();
    }

    public function getAdminEditor($draftHash, array $params = null, $referrer = null)
    {
        return $this->getEditor($draftHash, $params);
    }

    public function getAdminDraft($draftHash, $quoteId)
    {
        return $this->getDraft($draftHash);
    }

    public function getAdminPDF($draftHash, $quoteId)
    {
        // TODO: Implement getAdminPDF() method.
    }
}