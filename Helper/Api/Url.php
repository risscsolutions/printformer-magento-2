<?php
namespace Rissc\Printformer\Helper\Api;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Rissc\Printformer\Helper\Api\Url\V1 as V1Helper;
use Rissc\Printformer\Helper\Api\Url\V2 as V2Helper;

class Url
    extends AbstractHelper
{
    /** @var V1Helper|V2Helper */
    protected $_versionHelper = null;

    /** @var string */
    protected $_printformerBaseUrl = null;

    /** @var string */
    protected $_userCreateUrl = null;

    /** @var string */
    protected $_createDraftUrl = null;

    /** @var string */
    protected $_editorUrl = null;

    /** @var string */
    protected $_authUrl = null;

    /** @var string */
    protected $_draftProcessingUrl = null;

    /** @var StoreManagerInterface */
    protected $_storeManager;

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

    public function initVersionHelper($isV2Api = false)
    {
        $objm = ObjectManager::getInstance();
        if($isV2Api) {
            $this->_versionHelper = $objm->create('Rissc\Printformer\Helper\Api\Url\V2');
        } else {
            $this->_versionHelper = $objm->create('Rissc\Printformer\Helper\Api\Url\V1');
        }
    }

    /**
     * @param int    $productId
     * @param int    $masterId
     * @param string $draftId
     * @param array  $params = []
     *
     * @return string
     */
    public function getEditorEntry($productId, $masterId, $draftId, $params = [])
    {
        return $this->_versionHelper->getEditorEntry($productId, $masterId, $draftId, $params);
    }

    /**
     * @return string
     */
    public function getPrintformerBaseUrl()
    {
        return $this->_versionHelper->getPrintformerBaseUrl();
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->_versionHelper->getUser();
    }

    /**
     * @param string $draftHash
     *
     * @return string
     */
    public function getDraft($draftHash = null)
    {
        return $this->_versionHelper->getDraft($draftHash);
    }

    /**
     * @param       $draftHash
     * @param array $params
     *
     * @return string
     */
    public function getEditor($draftHash, $params = [])
    {
        return $this->_versionHelper->getEditor($draftHash, $params);
    }

    /**
     * @return string
     */
    public function getAuth()
    {
        return $this->_versionHelper->getAuth();
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
     * @return string
     */
    public function getDraftProcessingUrl()
    {
        return $this->_versionHelper->getDraftProcessingUrl();
    }

    /**
     * @param string $draftHash
     *
     * @return string
     */
    public function getThumbnailUrl($draftHash) {
        return $this->_versionHelper->getThumbnailUrl($draftHash);
    }

    /**
     * @param string $draftHash
     *
     * @return string
     */
    public function getPDFUrl($draftHash) {
        return $this->_versionHelper->getPDFUrl($draftHash);
    }
}