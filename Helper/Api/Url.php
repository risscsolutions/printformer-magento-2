<?php
namespace Rissc\Printformer\Helper\Api;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Rissc\Printformer\Helper\Api\Url\V1 as V1Helper;
use Rissc\Printformer\Helper\Api\Url\V2 as V2Helper;
use Rissc\Printformer\Model\Config\Source\Redirect;
use Rissc\Printformer\Helper\Config;

class Url
    extends AbstractHelper
    implements VersionInterface
{
    const API_URL_CALLBACKORDEREDSTATUS = 'printformer/api/callbackOrderedStatus';

    /** @var V1Helper|V2Helper */
    protected $_versionHelper = null;

    /** @var StoreManagerInterface */
    protected $_storeManager;

    /** @var Config  */
    protected $config;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Config $config
    )
    {
        $this->_storeManager = $storeManager;
        $this->config = $config;

        parent::__construct($context);

        $this->initVersionHelper($this->config->isV2Enabled());
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
    public function initVersionHelper($isV2Api = false)
    {
        $objm = ObjectManager::getInstance();
        if($isV2Api) {
            $this->_versionHelper = $objm->create('Rissc\Printformer\Helper\Api\Url\V2');
        } else {
            $this->_versionHelper = $objm->create('Rissc\Printformer\Helper\Api\Url\V1');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setStoreId($storeId)
    {
        return $this->_versionHelper->setStoreId($storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getStoreId()
    {
        return $this->_versionHelper->getStoreId();
    }

    /**
     * {@inheritdoc}
     */
    public function getEditorEntry($productId, $masterId, $draftHash, $params = [], $intent = null, $user = null)
    {
        return $this->_versionHelper->getEditorEntry($productId, $masterId, $draftHash, $params, $intent, $user);
    }

    /**
     * {@inheritdoc}
     */
    public function getPrintformerBaseUrl()
    {
        return $this->_versionHelper->getPrintformerBaseUrl();
    }

    /**
     * {@inheritdoc}
     */
    public function getUser()
    {
        return $this->_versionHelper->getUser();
    }

    /**
     * {@inheritdoc}
     */
    public function getDraft($draftHash = null, $quoteId = null)
    {
        return $this->_versionHelper->getDraft($draftHash, $quoteId);
    }

    /**
     * {@inheritdoc}
     */
    public function getEditor($draftHash, $user = null, $params = [])
    {
        return $this->_versionHelper->getEditor($draftHash, $user, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function getAuth()
    {
        return $this->_versionHelper->getAuth();
    }

    /**
     * {@inheritdoc}
     */
    public function getDraftProcessing($draftHashes = [], $quoteId = null)
    {
        return $this->_versionHelper->getDraftProcessing($draftHashes, $quoteId);
    }

    /**
     * {@inheritdoc}
     */
    public function getThumbnail($draftHash)
    {
        return $this->_versionHelper->getThumbnail($draftHash);
    }

    /**
     * {@inheritdoc}
     */
    public function getPDF($draftHash, $quoteId = null)
    {
        return $this->_versionHelper->getPDF($draftHash, $quoteId);
    }

    /**
     * {@inheritdoc}
     */
    public function getProducts()
    {
        return $this->_versionHelper->getProducts();
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminProducts()
    {
        return $this->_versionHelper->getAdminProducts();
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminPDF($draftHash, $quoteId)
    {
        return $this->_versionHelper->getAdminPDF($draftHash, $quoteId);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminEditor($draftHash, array $params = null, $referrer = null)
    {
        return $this->_versionHelper->getAdminEditor($draftHash, $params, $referrer);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminDraft($draftHash, $quoteId)
    {
        return $this->_versionHelper->getAdminDraft($draftHash, $quoteId);
    }

    /**
     * {@inheritdoc}
     */
    public function getDraftDelete($draftHash)
    {
        return $this->_versionHelper->getDraftDelete($draftHash);
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirect(ProductInterface $product = null, array $redirectParams = null)
    {
        if (!$redirectParams){
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
}