<?php
namespace Rissc\Printformer\Helper;

use DateInterval;
use DateTimeImmutable;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;
use Magento\Store\Model\StoreManagerInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Rissc\Printformer\Setup\InstallSchema;

class Config extends AbstractHelper
{
    const XML_PATH_CONFIG_DRAFT_UPDATE              = 'printformer/draft/draft_update';
    const XML_PATH_CONFIG_DRAFT_UPDATE_ORDER_ID     = 'printformer/draft/draft_update_order_id';

    const XML_PATH_V2_API_KEY                       = 'printformer/version2group/v2apiKey';
    const XML_PATH_V2_IDENTIFIER                    = 'printformer/version2group/v2identifier';
    const XML_PATH_V2_URL                           = 'printformer/version2group/v2url';
    const XML_PATH_V2_NAME                           = 'printformer/version2group/v2clientName';

    const XML_PATH_CONFIG_ENABLED                   = 'printformer/general/enabled';
    const XML_PATH_CONFIG_HOST                      = 'printformer/general/remote_host';
    const XML_PATH_CONFIG_LICENSE                   = 'printformer/general/license_key';
    const XML_PATH_CONFIG_SECRET                    = 'printformer/general/secret_word';
    const XML_PATH_CONFIG_LOCALE                    = 'printformer/general/locale';
    const XML_PATH_CONFIG_STATUS                    = 'printformer/general/order_status';
    const XML_PATH_CONFIG_DISPLAY_MODE              = 'printformer/general/display_mode';
    const XML_PATH_CONFIG_FRAME_FULLSCREEN          = 'printformer/general/frame_fullscreen';

    const XML_PATH_CONFIG_REDIRECT_ON_CANCEL        = 'printformer/general/redirect_on_cancel';
    const XML_PATH_CONFIG_REDIRECT                  = 'printformer/general/redirect_after_config';
    const XML_PATH_CONFIG_REDIRECT_URL              = 'printformer/general/redirect_alt_url';
    const XML_PATH_CONFIG_OPEN_EDITOR_PREVIEW_TEXT  = 'printformer/general/open_editor_preview_text';
    const XML_PATH_CONFIG_SKIP_CONFIG               = 'printformer/general/allow_skip_config';
    const XML_PATH_CONFIG_WISHLIST_HINT             = 'printformer/general/guest_wishlist_hint';
    const XML_PATH_CONFIG_EXPIRE_DATE               = 'printformer/general/expire_date';
    const XML_PATH_CONFIG_EDIT_TEXT                 = 'printformer/general/cart_edit_text';
    const XML_PATH_CONFIG_IMAGE_PREVIEW             = 'printformer/general/product_image_preview';
    const XML_PATH_CONFIG_IMAGE_PREVIEW_WIDTH       = 'printformer/general/product_image_preview_width';
    const XML_PATH_CONFIG_IMAGE_PREVIEW_HEIGHT      = 'printformer/general/product_image_preview_height';

    const XML_PATH_CONFIG_IMAGE_THUMB_WIDTH         = 'printformer/general/product_image_thumbnail_width';
    const XML_PATH_CONFIG_IMAGE_THUMB_HEIGHT        = 'printformer/general/product_image_thumbnail_height';
    const XML_PATH_CONFIG_BUTTON_TEXT               = 'printformer/general/config_button_text';
    const XML_PATH_CONFIG_BUTTON_CSS                = 'printformer/general/config_button_css';
    const XML_PATH_CONFIG_SHOW_DELETE_BUTTON        = 'printformer/general/delete_draft_button';
    const XML_PATH_CONFIG_DELETE_CONFIRM_TEXT       = 'printformer/general/delete_confirm_text';
    const XML_PATH_CONFIG_TRANSFER_USER_DATA        = 'printformer/general/transfer_user_data';
    const XML_PATH_CONFIG_UPLOAD_TEMPLATE_ID        = 'printformer/general/printformer_upload_template_id';

    const XML_PATH_CONFIG_FILTER_FOR_DEFAULT_STORE = 'printformer/general/filter_for_default_store';
    const XML_PATH_CONFIG_FILTER_FOR_CONFIGURABLE_PRODUCT = 'printformer/general/filter_for_configurable_product';

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

    const REGISTRY_KEY_WISHLIST_NEW_ITEM_ID         = 'printformer_new_wishlist_item_id';
    const CONFIGURABLE_TYPE_CODE = Configurable::TYPE_CODE;

    public const XML_PATH_INVENTORY_MANAGE_STOCK_CONFIG_ENABLED = 'cataloginventory/item_options/manage_stock';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param EncryptorInterface $encryptor
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;
    }

    /**
     * @return int
     */
    public function getStoreIdFromRequest()
    {
        $params = $this->_request->getParams();
        return $this->_request->getParam('store_id', Store::DEFAULT_STORE_ID);
    }

    /**
     * @return int
     */
    public function getWebsiteIdFromRequest()
    {
        $params = $this->_request->getParams();
        return $this->_request->getParam('website_id');
    }

    /**
     * @param $config
     * @return bool
     */
    public function getConfigValue($config, $isSetFlag = false, $storeId = false, $websiteId = false)
    {
        $resultValue = false;

        if ((!is_numeric($storeId) && !is_numeric($websiteId))) {
            $storeId = $this->getStoreIdFromRequest();
            $websiteId = $this->getWebsiteIdFromRequest();
        }

        if ($websiteId) {
            $scope = ScopeInterface::SCOPE_WEBSITE;
        } else {
            $scope = ScopeInterface::SCOPE_STORE;
        }

        if ($scope == ScopeInterface::SCOPE_WEBSITE) {
            $scopeId = $websiteId;
        } else {
            $scopeId = $storeId;
        }

        if ($scope !== false && $scopeId !== false) {
            if ($isSetFlag) {
                $resultValue = $this->scopeConfig->isSetFlag(
                    $config,
                    $scope,
                    $scopeId
                );
            } else {
                $resultValue = $this->scopeConfig->getValue(
                    $config,
                    $scope,
                    $scopeId
                );
            }
        }

        return $resultValue;
    }

    /**
     * @return boolean
     */
    public function isEnabled($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_ENABLED, true, $storeId, $websiteId);
    }

    /**
     * @return int
     */
    public function getDisplayMode($storeId = false, $websiteId = false)
    {
        return intval($this->getConfigValue(self::XML_PATH_CONFIG_DISPLAY_MODE, false, $storeId, $websiteId));
    }

    /**
     * @return bool
     */
    public function isEditorFullscreenEnabled()
    {
        return $this->getDisplayMode() == 1;
    }

    /**
     * @return bool
     */
    public function isFrameEnabled()
    {
        return $this->getDisplayMode() == 2;
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function isFullscreenButtonEnabled($storeId = false, $websiteId = false)
    {
        return $this->isFrameEnabled() && $this->getConfigValue(self::XML_PATH_CONFIG_FRAME_FULLSCREEN, true, $storeId, $websiteId);
    }

    /**
     * @return string
     */
    public function getHost($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_HOST, false, $storeId, $websiteId);
    }

    /**
     * @return string
     */
    public function getLicense($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_LICENSE, false, $storeId, $websiteId);
    }

    /**
     * @return string
     */
    public function getSecret($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_SECRET, false, $storeId, $websiteId);
    }

    /**
     * @return string
     */
    public function getLocale($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_LOCALE, false, $storeId, $websiteId);
    }

    /**
     * @return array
     */
    public function getOrderStatus($storeId = false, $websiteId = false)
    {
        $configValue = $this->getConfigValue(self::XML_PATH_CONFIG_DRAFT_UPDATE_ORDER_ID, false, $storeId, $websiteId);
        return explode(',', $configValue ?? '');
    }

    /**
     * @return int
     */
    public function getOrderDraftUpdate($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_DRAFT_UPDATE, false, $storeId, $websiteId);
    }

    /**
     * @return mixed
     */
    public function getOrderDraftUpdateOrderId($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_DRAFT_UPDATE_ORDER_ID, false, $storeId, $websiteId);
    }

    /**
     * @return bool
     */
    public function getRedirectProductOnCancel($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_REDIRECT_ON_CANCEL, false, $storeId, $websiteId) == '1';
    }

    /**
     * @return string
     */
    public function getConfigRedirect($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_REDIRECT, false, $storeId, $websiteId);
    }

    /**
     * @return string
     */
    public function getRedirectAlt($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_REDIRECT_URL, false, $storeId, $websiteId);
    }

    /**
     * @return string
     */
    public function getOpenEditorPreviewText($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_OPEN_EDITOR_PREVIEW_TEXT, false, $storeId, $websiteId);
    }

    /**
     * @return string
     */
    public function isAllowSkipConfig($storeId = false, $websiteId = false)
    {
        return intval($this->getConfigValue(self::XML_PATH_CONFIG_SKIP_CONFIG, false, $storeId, $websiteId));
    }

    /**
     * @return string
     */
    public function getGuestWishlistHint($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_WISHLIST_HINT, false, $storeId, $websiteId);
    }

    /**
     * @return string
     */
    public function isUseImagePreview($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_IMAGE_PREVIEW, true, $storeId, $websiteId);
    }

    /**
     * @return int
     */
    public function getImagePreviewWidth($storeId = false, $websiteId = false)
    {
        return (int)$this->getConfigValue(self::XML_PATH_CONFIG_IMAGE_PREVIEW_WIDTH, false, $storeId, $websiteId);
    }

    /**
     * @return int
     */
    public function getImagePreviewHeight($storeId = false, $websiteId = false)
    {
        return (int)$this->getConfigValue(self::XML_PATH_CONFIG_IMAGE_PREVIEW_HEIGHT, false, $storeId, $websiteId);
    }

    /**
     * @return int
     */
    public function getImageThumbnailWidth($storeId = false, $websiteId = false)
    {
        return (int)$this->getConfigValue(self::XML_PATH_CONFIG_IMAGE_THUMB_WIDTH, false, $storeId, $websiteId);
    }

    /**
     * @return int
     */
    public function getImageThumbnailHeight($storeId = false, $websiteId = false)
    {
        return (int)$this->getConfigValue(self::XML_PATH_CONFIG_IMAGE_THUMB_HEIGHT, false, $storeId, $websiteId);
    }

    /**
     * @return string
     */
    public function getEditText($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_EDIT_TEXT, false, $storeId, $websiteId);
    }

    /**
     * @return DateTimeImmutable
     */
    public function getExpireDate($storeId = false, $websiteId = false): DateTimeImmutable
    {
        $days = $this->getConfigValue(self::XML_PATH_CONFIG_EXPIRE_DATE, false, $storeId, $websiteId);

        $dateTimeImmutable = new DateTimeImmutable();
        $expireDateTimeImmutable = $dateTimeImmutable;

        try {
            $dateTimeInterval = new DateInterval('P' . $days . 'D');
            $expireDateTimeImmutable = $dateTimeImmutable->add($dateTimeInterval);
        } catch (\Exception $e) {
            $this->_logger->warning('invalid datetime interval format, please verify');
        }

        return $expireDateTimeImmutable;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getExpireDateTimeStamp($storeId = false, $websiteId = false): string
    {
        $days = $this->getConfigValue(self::XML_PATH_CONFIG_EXPIRE_DATE, false, $storeId, $websiteId);

        return (new \DateTime())->add(\DateInterval::createFromDateString('+'.$days.' days'))->getTimestamp();
    }

    /**
     * @return string
     */
    public function getButtonText($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_BUTTON_TEXT, false, $storeId, $websiteId);
    }

    /**
     * @return string
     */
    public function getUploadTemplateId($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_UPLOAD_TEMPLATE_ID, false, $storeId, $websiteId);
    }

    /**
     * @return string
     */
    public function getButtonCss($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_BUTTON_CSS, false, $storeId, $websiteId);
    }

    /**
     * @return string
     */
    public function isFormatChangeNotice($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_FORMAT_CHANGE_NOTICE, true, $storeId, $websiteId);
    }

    /**
     * @return string
     */
    public function getCloseNoticeText($storeId = false, $websiteId = false)
    {
        $text = $this->getConfigValue(self::XML_PATH_CONFIG_CLOSE_NOTICE_TEXT, false, $storeId, $websiteId);

        if($text == "") {
            $text = 'Are you sure?';
        }

        return $text;
    }

    /**
     * @return string
     */
    public function getFormatNoticeText($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_FORMAT_NOTICE_TEXT, false, $storeId, $websiteId);
    }

    /**
     * @return string
     */
    public function getFormatQueryParameter($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_FORMAT_QUERY_PARAMETER, false, $storeId, $websiteId);
    }

    /**
     * @return string
     */
    public function isFormatAttributeEnabled($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_FORMAT_ATTRIBUTE_ENABLED, true, $storeId, $websiteId);
    }

    /**
     * @return string
     */
    public function getFormatAttributeName($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_FORMAT_ATTRIBUTE_NAME, false, $storeId, $websiteId);
    }

    /**
     * @return array
     */
    public function getFormatAttributeValues($storeId = false, $websiteId = false)
    {
        $value = $this->getConfigValue(self::XML_PATH_CONFIG_FORMAT_ATTRIBUTE_VALUES, false, $storeId, $websiteId);
        return unserialize($value);
    }

    /**
     * @return string
     */
    public function isFormatOptionEnabled($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_FORMAT_OPTION_ENABLED, true, $storeId, $websiteId);
    }

    /**
     * @return string
     */
    public function getFormatOptionName($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_FORMAT_OPTION_NAME, false, $storeId, $websiteId);
    }

    /**
     * @return array
     */
    public function getFormatOptionValues($storeId = false, $websiteId = false)
    {
        $value = $this->getConfigValue(self::XML_PATH_CONFIG_FORMAT_OPTION_VALUES, false, $storeId, $websiteId);
        return unserialize($value);
    }

    /**
     * @return string
     */
    public function getColorQueryParameter($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_COLOR_QUERY_PARAMETER, false, $storeId, $websiteId);
    }

    /**
     * @return string
     */
    public function isColorAttributeEnabled($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_COLOR_ATTRIBUTE_ENABLED, true, $storeId, $websiteId);
    }

    /**
     * @return string
     */
    public function getColorAttributeName($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_COLOR_ATTRIBUTE_NAME, false, $storeId, $websiteId);
    }

    /**
     * @return array
     */
    public function getColorAttributeValues($storeId = false, $websiteId = false)
    {
        $value = $this->getConfigValue(self::XML_PATH_CONFIG_COLOR_ATTRIBUTE_VALUES, false, $storeId, $websiteId);
        return unserialize($value);
    }

    /**
     * @return string
     */
    public function isColorOptionEnabled($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_COLOR_OPTION_ENABLED, true, $storeId, $websiteId);
    }

    /**
     * @return string
     */
    public function getColorOptionName($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_COLOR_OPTION_NAME, false, $storeId, $websiteId);
    }

    /**
     * @return array
     */
    public function getColorOptionValues($storeId = false, $websiteId = false)
    {
        $value = $this->getConfigValue(self::XML_PATH_CONFIG_COLOR_OPTION_VALUES, false, $storeId, $websiteId);
        return unserialize($value);
    }

    /**
     * @param int $storeId
     * @return mixed
     */
    public function getClientApiKey($storeId = false, $websiteId = false)
    {
        $decryptedKey = null;
        $encryptedKey = $this->getConfigValue(self::XML_PATH_V2_API_KEY, false, $storeId, $websiteId);

        if (!empty($encryptedKey)){
            $decryptedKey = $this->encryptor->decrypt($encryptedKey);
        }

        return $decryptedKey;
    }

    /**
     * @return string
     */
    public function getClientIdentifier($storeId = false, $websiteId = false)
    {
        if ($storeId === false && $websiteId === false) {
            $storeId = $this->storeManager->getStore()->getId();
            $websiteId = $this->storeManager->getWebsite()->getId();
        }
        return $this->getConfigValue(self::XML_PATH_V2_IDENTIFIER, false, $storeId, $websiteId);
    }

    /**
     * @return string
     */
    public function getClientUrl($storeId = false, $websiteId = false)
    {
        if ($storeId === false && $websiteId === false) {
            $storeId = $this->storeManager->getStore()->getId();
            $websiteId = $this->storeManager->getWebsite()->getId();
        }
        $clientUrl = $this->getConfigValue(self::XML_PATH_V2_URL, false, $storeId, $websiteId);
        $resultClientUrl = '';
        if (!empty($clientUrl)) {
            $resultClientUrl = rtrim($clientUrl, "/");
        }

        return $resultClientUrl;
    }

    /**
     * @return bool
     */
    public function isDeleteButtonEnabled($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_SHOW_DELETE_BUTTON, true, $storeId, $websiteId);
    }

    /**
     * @param $storeId
     * @param $websiteId
     * @return bool
     */
    public function filterForDefaultStore($storeId = false, $websiteId = false)
    {
        if ($storeId === false && $websiteId === false) {
            $storeId = $this->storeManager->getStore()->getId();
            $websiteId = $this->storeManager->getWebsite()->getId();
        }
        return $this->getConfigValue(self::XML_PATH_CONFIG_FILTER_FOR_DEFAULT_STORE, true, $storeId, $websiteId);
    }

    /**
     * @param $storeId
     * @param $websiteId
     * @return bool
     */
    public function filterForConfigurableProduct($storeId = false, $websiteId = false)
    {
        if ($storeId === false && $websiteId === false) {
            $storeId = $this->storeManager->getStore()->getId();
            $websiteId = $this->storeManager->getWebsite()->getId();
        }
        return $this->getConfigValue(self::XML_PATH_CONFIG_FILTER_FOR_CONFIGURABLE_PRODUCT, true, $storeId, $websiteId);
    }

    /**
     * @return string
     */
    public function getDeleteConfirmText($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_DELETE_CONFIRM_TEXT, false, $storeId, $websiteId);
    }

    /**
     * Get Drafids from quote-item or if product is configurable and has children-item, then from first child-item
     *
     * @param $item
     * @return string
     */
    public function getDraftIdsFromSpecificItemType($item)
    {
        if($item->getProductType() === $this::CONFIGURABLE_TYPE_CODE) {
            $childItems = $item->getChildren();
            if (!empty($childItems)) {
                $firstChildItem = $childItems[0];
                $draftIds = $firstChildItem->getPrintformerDraftid();
            } else {
                $draftIds = $item->getPrintformerDraftid();
            }
        } else {
            $draftIds = $item->getPrintformerDraftid();
        }

        return $draftIds;
    }

    /**
     * Add draft ids to quote-item depending on product type
     *
     * @param $item
     * @param $draftIds
     * @return Item
     */
    public function setDraftsOnItemType($item, $draftIds): Item
    {
        if($item->getProductType() === $this::CONFIGURABLE_TYPE_CODE) {
            $childItems = $item->getChildren();
            if (!empty($childItems)){
                $firstChildItem = $childItems[0];
                $firstChildItem->setData(InstallSchema::COLUMN_NAME_DRAFTID, $draftIds);
                $item->unsetData(InstallSchema::COLUMN_NAME_DRAFTID);
            }
        } else {
            if (empty($item->getParentItem())) {
                $item->setData(InstallSchema::COLUMN_NAME_DRAFTID, $draftIds);
            }
        }

        return $item;
    }

    /**
     * @return string
     */
    public function isDataTransferEnabled($storeId = false, $websiteId = false)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_TRANSFER_USER_DATA, true, $storeId, $websiteId);
    }

    /**
     * @return boolean
     */
    public function isConfigManageStockEnabled(): bool
    {
        return $this->getConfigValue(self::XML_PATH_INVENTORY_MANAGE_STOCK_CONFIG_ENABLED, false);
    }
}
