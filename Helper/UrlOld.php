<?php

namespace Rissc\Printformer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\UrlInterface;
use Rissc\Printformer\Model\Config\Source\Redirect;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class UrlOld extends AbstractHelper
{
    const ROLE_USER  = '0';
    const ROLE_ADMIN = '1';

    const URI_ADMIN_PRODUCTS            = 'api/admin/products';
    const URI_ADMIN_GETPDF              = 'api/admin/getpdf';
    const URI_USER_EDITOR               = 'user/editor/editor';
    const URI_USER_DRAFTEDITOR          = 'editor';
    const URI_CUSTOMER_ORDERED          = 'api/customer/setdraftordered';
    const URI_CUSTOMER_DRAFTIMG         = 'api/customer/draftimage';
    const URI_CUSTOMER_DELETE           = 'api/customer/delete';
    const URI_CUSTOMER_DRAFT            = 'api-ext/draft';
    const URI_CUSTOMER_USER             = 'api-ext/user';
    const URI_ADMIN_GETDRAFT            = 'api/admin/getdraft';
    const URI_CUSTOMER_PDF_PROCESSING   = 'api-ext/pdf-processing';
    const URI_CUSTOMER_CREATE_DRAFT     = 'some/path/on/server';
    const URI_CUSTOMER_AUTH             = 'auth';
    const URI_CUSTOMER_PDF              = 'api-ext/files/draft/';

    /**
     * @var UrlInterface
     */
    protected $url;

    /**
     * @var Config
     */
    protected $config;

    protected $_scopeConfig;

    protected $printformerUrl;

    /**
     * @param Context $context
     * @param Config $config
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Config $config
    ) {
        parent::__construct($context);
        $this->url = $context->getUrlBuilder();
        $this->config = $config;
        $this->_scopeConfig = $scopeConfig;
        $this->printformerUrl = $this->config->getClientUrl();
    }

    public function getPrintformerUrl() {
        return $this->printformerUrl;
    }

    public function getPrintformerDraftUrl() {
        return $this->printformerUrl . "/" . self::URI_CUSTOMER_DRAFT;
    }

    public function getPrintformerUserUrl() {
        return $this->printformerUrl . "/" . self::URI_CUSTOMER_USER;
    }

    public function getAuthEndpointUrl() {
        return $this->printformerUrl . "/" . self::URI_CUSTOMER_AUTH;
    }

    public function getPdfUrl($draftId) {
        return $this->printformerUrl . "/" . self::URI_CUSTOMER_PDF . $draftId . "/low-res"; // print; low-res
    }

    /**
     * @return string
     */
    public function getAdminProductsUrl()
    {
        //@todo use ZF URL builder? Implement generic method for building printformer URLs?
        $urlParts = array(
            $this->getHost(),
            self::URI_ADMIN_PRODUCTS,
            $this->getApikeyParamName(),
            $this->getApikey(),
            $this->getAuthkeyParamName(),
            $this->getAuthkey(self::ROLE_ADMIN)
        );
        return implode('/', $urlParts);
    }

    /**
     * @param integer $draftId
     * @param integer $quoteId
     * @return string
     */
    public function getAdminPdfUrl($draftId, $quoteId, $noMD5 = false)
    {
        $wkId = $quoteId;
        if(!$noMD5) {
            $wkId = md5($wkId);
        }
        //@todo use ZF URL builder? Implement generic method for building printformer URLs?
        $urlParts = array(
            $this->getHost(),
            self::URI_ADMIN_GETPDF,
            $this->getApikeyParamName(),
            $this->getApikey(),
            $this->getAuthkeyParamName(),
            $this->getAuthkey(self::ROLE_ADMIN),
            'risscw2pdraft',
            $wkId.$draftId
        );
        return implode('/', $urlParts);
    }

    /**
     * @param            $draftId
     * @param array|null $params
     * @param string       $referrer
     *
     * @return string
     */
    public function getAdminEditorUrl($draftId, array $params = null, $referrer = null)
    {
        //@todo use ZF URL builder? Implement generic method for building printformer URLs?
        $urlParts = array(
            $this->getHost(),
            self::URI_USER_EDITOR,
            $this->getApikeyParamName(),
            $this->getApikey(),
            $this->getAuthkeyParamName(),
            $this->getAuthkey(self::ROLE_ADMIN),
            'locale',
            $this->getLocale(),
            'risscw2pdraft',
            $draftId
        );

        if (is_array($params)) {
            foreach ($params as $key => $value) {
                $urlParts[] = $key;
                $urlParts[] = $value;
            }
        }

        if(!$referrer)
        {
            $referrer = $this->url->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true]);
        }

        return implode('/', $urlParts) . '?risscw2preferer=' . urlencode($referrer);
    }

    /**
     * @param       $productId
     * @param       $identifier
     * @param null  $intent
     * @param null  $user
     * @param array $editParams
     *
     * @return string
     */
    public function getEditorUrl($productId, $identifier, $intent = null, $user = null, $editParams = [])
    {
        $paramsArray = [
            'identifier' => $identifier,
            'product_id' => $productId,
            'intent' => $intent,
            'user' => $user
        ];
        if(!empty($editParams)) {
            $paramsArray = array_merge($editParams, $paramsArray);
        }
        return $this->_getUrl('printformer/editor/open', $paramsArray);
    }

    public function getDraftEditorUrl($draftId)
    {
        $urlParts = array(
            $this->getPrintformerUrl(),
            self::URI_USER_DRAFTEDITOR,
            $draftId
        );

        return implode('/', $urlParts) . (!empty($authParams) ? '?' . implode('&', $authParams) : '');
    }

    /**
     * @param array $draftIds
     * @param $quoteId
     * @return string
     */
    public function getDraftOrderedUrl(array $draftIds, $quoteId)
    {
        //@todo use ZF URL builder? Implement generic method for building printformer URLs?
        $urlParts = array(
            $this->getHost(),
            self::URI_CUSTOMER_ORDERED,
            $this->getApikeyParamName(),
            $this->getApikey(),
            $this->getAuthkeyParamName(),
            $this->getAuthkey(self::ROLE_ADMIN)
        );

        $data = [
            $draftIds,
            md5($quoteId)
        ];

        return implode('/', $urlParts) . '?cartdata=' . urlencode(json_encode($data));
    }

    /**
     * @param $draftId
     * @return string
     */
    public function getThumbImgUrl($draftId)
    {
        //@todo use ZF URL builder? Implement generic method for building printformer URLs?
        $urlParts = array(
            $this->getHost(),
            self::URI_CUSTOMER_DRAFTIMG,
            $this->getApikeyParamName(),
            $this->getApikey(),
            $this->getAuthkeyParamName(),
            $this->getAuthkey(),
            'risscw2pdraftid',
            $draftId
        );
        return implode('/', $urlParts);
    }

    /**
     * @param $draftId
     * @return string
     */
    public function getDraftUrl($draftId = null)
    {
        //@todo use ZF URL builder? Implement generic method for building printformer URLs?
        $urlParts = array(
            $this->getHost(),
            self::URI_CUSTOMER_DRAFT,
            !$draftId ? '' : $draftId
        );

        $authParams = [
            $this->getApikeyParamName() . '=' . $this->getApikey(),
            $this->getAuthkeyParamName() . '=' . $this->getAuthkey(self::ROLE_ADMIN),
        ];

        return implode('/', $urlParts) . (!empty($authParams) ? '?' . implode('&', $authParams) : '');
    }

    /**
     * @param string $draftId
     * @return string
     */
    public function getPdfProcessingUrl($draftId)
    {
        $urlParts = array(
            $this->getHost(),
            self::URI_CUSTOMER_PDF_PROCESSING
        );

        $authParams = [
            $this->getApikeyParamName() . '=' . $this->getApikey(),
            $this->getAuthkeyParamName() . '=' . $this->getAuthkey(self::ROLE_ADMIN),
        ];

        return implode('/', $urlParts) . (!empty($authParams) ? '?' . implode('&', $authParams) : '');
    }

    /**
     * @param $draftId
     * @return string
     */
    public function getDraftDeleteUrl($draftId)
    {
        //@todo use ZF URL builder? Implement generic method for building printformer URLs?
        $urlParts = array(
            $this->getHost(),
            self::URI_CUSTOMER_DELETE,
            $this->getApikeyParamName(),
            $this->getApikey(),
            $this->getAuthkeyParamName(),
            $this->getAuthkey(self::ROLE_ADMIN),
            'risscw2pdraftid',
            $draftId
        );
        return implode('/', $urlParts);
    }


    /**
     * @return string
     */
    protected function getHost()
    {
        return $this->config->getHost();
    }

    /**
     * @return string
     */
    protected function getLocale()
    {
        return $this->config->getLocale();
    }

    /**
     * @return string
     */
    protected function getApikeyParamName()
    {
        return md5(substr($this->config->getSecret(), 0, 1));
    }

    /**
     * @return string
     */
    protected function getApikey()
    {
        return md5($this->config->getSecret() . $this->config->getLicense());
    }

    /**
     * @return string
     */
    protected function getAuthkeyParamName()
    {
        return md5(substr($this->config->getSecret(), 1, 1));
    }

    /**
     * @param string $roleId
     * @return string
     */
    protected function getAuthkey($roleId = self::ROLE_USER)
    {
        return md5($this->config->getSecret() . $roleId);
    }

    /**
     * @param ProductInterface $product
     * @param array $redirectParams
     * @return string
     */
    public function getRedirectUrl(ProductInterface $product = null, array $redirectParams = null)
    {
        if (!$redirectParams){
            switch ($this->config->getConfigRedirect()) {
                case Redirect::CONFIG_REDIRECT_URL_ALT:
                    return $this->config->getRedirectAlt();
                case Redirect::CONFIG_REDIRECT_URL_CART:
                    return $this->url->getUrl('checkout/cart', ['_use_rewrite' => true]);
                case Redirect::CONFIG_REDIRECT_URL_PRODUCT:
                default:
                    return $product->getUrlModel()->getUrl($product);
            }
        }

        return $this->url->getUrl($redirectParams['controller'], $redirectParams['params']);
    }

    /**
     * @param $draftId
     * @param $quoteId
     *
     * @return string
     */
    public function getAdminDraftUrl($draftId, $quoteId)
    {
        $urlParts = array(
            $this->getHost(),
            self::URI_ADMIN_GETDRAFT,
            $this->getApikeyParamName(),
            $this->getApikey(),
            $this->getAuthkeyParamName(),
            $this->getAuthkey(self::ROLE_ADMIN),
            'risscw2pdraft',
            md5($quoteId).$draftId
        );
        return implode('/', $urlParts);
    }
}
