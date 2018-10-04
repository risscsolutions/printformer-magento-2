<?php
namespace Rissc\Printformer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Model\Session as CustomerSession;

class Config extends AbstractHelper
{
    const XML_PATH_V2_ENABLED                       = 'printformer/version2group/version2';
    const XML_PATH_V2_API_KEY                       = 'printformer/version2group/v2apiKey';
    const XML_PATH_V2_IDENTIFIER                    = 'printformer/version2group/v2identifier';

    const XML_PATH_CONFIG_ENABLED                   = 'printformer/general/enabled';
    const XML_PATH_CONFIG_HOST                      = 'printformer/general/remote_host';
    const XML_PATH_CONFIG_LICENSE                   = 'printformer/general/license_key';
    const XML_PATH_CONFIG_SECRET                    = 'printformer/general/secret_word';
    const XML_PATH_CONFIG_LOCALE                    = 'printformer/general/locale';
    const XML_PATH_CONFIG_STATUS                    = 'printformer/general/order_status';

    const XML_PATH_CONFIG_REDIRECT_ON_CANCEL        = 'printformer/general/redirect_on_cancel';
    const XML_PATH_CONFIG_REDIRECT                  = 'printformer/general/redirect_after_config';
    const XML_PATH_CONFIG_REDIRECT_URL              = 'printformer/general/redirect_alt_url';
    const XML_PATH_CONFIG_SKIP_CONFIG               = 'printformer/general/allow_skip_config';
    const XML_PATH_CONFIG_WISHLIST_HINT             = 'printformer/general/guest_wishlist_hint';
    const XML_PATH_CONFIG_EDIT_TEXT                 = 'printformer/general/cart_edit_text';
    const XML_PATH_CONFIG_IMAGE_PREVIEW             = 'printformer/general/product_image_preview';
    const XML_PATH_CONFIG_IMAGE_PREVIEW_WIDTH       = 'printformer/general/product_image_preview_width';
    const XML_PATH_CONFIG_IMAGE_PREVIEW_HEIGHT      = 'printformer/general/product_image/preview_height';
    const XML_PATH_CONFIG_BUTTON_TEXT               = 'printformer/general/config_button_text';
    const XML_PATH_CONFIG_BUTTON_CSS                = 'printformer/general/config_button_css';
    const XML_PATH_CONFIG_SHOW_DELETE_BUTTON        = 'printformer/general/delete_draft_button';

    const XML_PATH_CONFIG_FORMAT_CHANGE_NOTICE      = 'printformer/format/change_notice';
    const XML_PATH_CONFIG_FORMAT_NOTICE_TEXT        = 'printformer/format/notice_text';
    const XML_PATH_CONFIG_CLOSE_NOTICE_TEXT         = 'printformer/general/close_text';
    const XML_PATH_CONFIG_FORMAT_QUERY_PARAMETER    = 'printformer/format/query_parameter';
    const XML_PATH_CONFIG_FORMAT_ATTRIBUTE_ENABLED  = 'printformer/format/attribute_enabled';
    const XML_PATH_CONFIG_FORMAT_ATTRIBUTE_NAME     = 'printformer/format/attribute_name';
    const XML_PATH_CONFIG_FORMAT_ATTRIBUTE_VALUES   = 'printformer/format/attribute_values';
    const XML_PATH_CONFIG_FORMAT_OPTION_ENABLED     = 'printformer/format/option_enabled';
    const XML_PATH_CONFIG_FORMAT_OPTION_NAME        = 'printformer/format/option_name';
    const XML_PATH_CONFIG_FORMAT_OPTION_VALUES      = 'printformer/format/option_values';

    const XML_PATH_CONFIG_COLOR_QUERY_PARAMETER     = 'printformer/color/query_parameter';
    const XML_PATH_CONFIG_COLOR_ATTRIBUTE_ENABLED   = 'printformer/color/attribute_enabled';
    const XML_PATH_CONFIG_COLOR_ATTRIBUTE_NAME      = 'printformer/color/attribute_name';
    const XML_PATH_CONFIG_COLOR_ATTRIBUTE_VALUES    = 'printformer/color/attribute_values';
    const XML_PATH_CONFIG_COLOR_OPTION_ENABLED      = 'printformer/color/option_enabled';
    const XML_PATH_CONFIG_COLOR_OPTION_NAME         = 'printformer/color/option_name';
    const XML_PATH_CONFIG_COLOR_OPTION_VALUES       = 'printformer/color/option_values';

    const XML_PATH_CONFIG_DRAFT_PROCESSING_TYPE     = 'printformer/general/processing_type';

    const REGISTRY_KEY_WISHLIST_NEW_ITEM_ID         = 'printformer_new_wishlist_item_id';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * Config constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param CustomerSession $customerSession
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->storeId = $this->storeManager->getStore()->getId();
        $this->_customerSession = $customerSession;
    }

    /**
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        if (!$this->storeId) {
            $this->setStoreId(\Magento\Store\Model\Store::DEFAULT_STORE_ID);
        }
        return $this->storeId;
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CONFIG_ENABLED, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CONFIG_HOST, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return string
     */
    public function getLicense()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CONFIG_LICENSE, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CONFIG_SECRET, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CONFIG_LOCALE, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return string
     */
    public function getOrderStatus()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CONFIG_STATUS, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return bool
     */
    public function getRedirectProductOnCancel()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CONFIG_REDIRECT_ON_CANCEL, ScopeInterface::SCOPE_STORE,
            $this->getStoreId()) == '1';
    }

    /**
     * @return string
     */
    public function getConfigRedirect()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CONFIG_REDIRECT, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return string
     */
    public function getRedirectAlt()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CONFIG_REDIRECT_URL, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return string
     */
    public function isAllowSkipConfig()
    {
        return intval($this->scopeConfig->getValue(self::XML_PATH_CONFIG_SKIP_CONFIG, ScopeInterface::SCOPE_STORE,
            $this->getStoreId()));
    }

    /**
     * @return string
     */
    public function getGuestWishlistHint()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CONFIG_WISHLIST_HINT, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return string
     */
    public function isUseImagePreview()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CONFIG_IMAGE_PREVIEW, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return int
     */
    public function getImagePreviewWidth()
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_CONFIG_IMAGE_PREVIEW_WIDTH, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return int
     */
    public function getImagePreviewHeight()
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_CONFIG_IMAGE_PREVIEW_HEIGHT, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return string
     */
    public function getEditText()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CONFIG_EDIT_TEXT, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }


    /**
     * @return string
     */
    public function getButtonText()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CONFIG_BUTTON_TEXT, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return string
     */
    public function getButtonCss()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CONFIG_BUTTON_CSS, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return string
     */
    public function isFormatChangeNotice()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CONFIG_FORMAT_CHANGE_NOTICE, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return string
     */
    public function getCloseNoticeText()
    {
        $text = $this->scopeConfig->getValue(self::XML_PATH_CONFIG_CLOSE_NOTICE_TEXT, ScopeInterface::SCOPE_STORE, $this->getStoreId());
        if($text == "")
        {
            $text = 'Are you sure?';
        }

        return $text;
    }

    /**
     * @return string
     */
    public function getFormatNoticeText()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CONFIG_FORMAT_NOTICE_TEXT, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return string
     */
    public function getFormatQueryParameter()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CONFIG_FORMAT_QUERY_PARAMETER, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return string
     */
    public function isFormatAttributeEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CONFIG_FORMAT_ATTRIBUTE_ENABLED, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return string
     */
    public function getFormatAttributeName()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CONFIG_FORMAT_ATTRIBUTE_NAME, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return array
     */
    public function getFormatAttributeValues()
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_CONFIG_FORMAT_ATTRIBUTE_VALUES, ScopeInterface::SCOPE_STORE, $this->getStoreId());
        return unserialize($value);
    }

    /**
     * @return string
     */
    public function isFormatOptionEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CONFIG_FORMAT_OPTION_ENABLED, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return string
     */
    public function getFormatOptionName()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CONFIG_FORMAT_OPTION_NAME, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return array
     */
    public function getFormatOptionValues()
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_CONFIG_FORMAT_OPTION_VALUES, ScopeInterface::SCOPE_STORE, $this->getStoreId());
        return unserialize($value);
    }

    /**
     * @return string
     */
    public function getColorQueryParameter()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CONFIG_COLOR_QUERY_PARAMETER, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return string
     */
    public function isColorAttributeEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CONFIG_COLOR_ATTRIBUTE_ENABLED, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return string
     */
    public function getColorAttributeName()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CONFIG_COLOR_ATTRIBUTE_NAME, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return array
     */
    public function getColorAttributeValues()
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_CONFIG_COLOR_ATTRIBUTE_VALUES, ScopeInterface::SCOPE_STORE, $this->getStoreId());
        return unserialize($value);
    }

    /**
     * @return string
     */
    public function isColorOptionEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CONFIG_COLOR_OPTION_ENABLED, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return string
     */
    public function getColorOptionName()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CONFIG_COLOR_OPTION_NAME, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @return array
     */
    public function getColorOptionValues()
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_CONFIG_COLOR_OPTION_VALUES, ScopeInterface::SCOPE_STORE, $this->getStoreId());
        return unserialize($value);
    }

    /**
     * @return string
     */
    public function getProcessingType()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CONFIG_DRAFT_PROCESSING_TYPE, ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    /**
     * @param int $storeId
     * @return bool
     */
    public function isV2Enabled($storeId = null)
    {
        if($storeId === null)
            $storeId = $this->getStoreId();

        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_V2_ENABLED,
            ScopeInterface::SCOPE_STORES, $storeId
        );
    }

    /**
     * @param int $storeId
     * @return mixed
     */
    public function getClientApiKey($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_V2_API_KEY,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * @return string
     */
    public function getClientIdentifier()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_V2_IDENTIFIER,
            ScopeInterface::SCOPE_STORES
        );
    }

    /**
     * @return bool
     */
    public function isDeleteButtonEnabled()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_CONFIG_SHOW_DELETE_BUTTON,
            ScopeInterface::SCOPE_STORES
        ) == '1';
    }
}