<?php

namespace Rissc\Printformer\Block\Catalog\Product\View;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\View\AbstractView;
use Magento\Catalog\Model\Session as CatalogSession;
use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Wishlist\Model\Item;
use Rissc\Printformer\Controller\Editor\Save;
use Rissc\Printformer\Helper\Config;
use Rissc\Printformer\Helper\Session;
use Rissc\Printformer\Helper\Api\Url;
use Rissc\Printformer\Model\Draft;
use Rissc\Printformer\Model\DraftFactory;
use Rissc\Printformer\Setup\InstallSchema;

class Printformer extends AbstractView
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var Url
     */
    protected $urlHelper;

    /**
     * @var Session
     */
    protected $sessionHelper;

    /**
     * @var DraftFactory
     */
    protected $draftFactory;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var Item
     */
    protected $wishlistItem;

    /**
     * @var CatalogSession
     */
    protected $_catalogSession;

    /**
     * @var int
     */
    protected $draftMasterId;

    /**
     * @var DataObject
     */
    protected $_capabilities = null;

    /**
     * Printformer constructor.
     * @param Config $configHelper
     * @param Url $urlHelper
     * @param Session $sessionHelper
     * @param DraftFactory $draftFactory
     * @param Cart $cart
     * @param Context $context
     * @param ArrayUtils $arrayUtils
     * @param Item $wishlistItem
     * @param CatalogSession $catalogSession
     * @param array $data
     */
    public function __construct(
        Config $configHelper,
        Url $urlHelper,
        Session $sessionHelper,
        DraftFactory $draftFactory,
        Cart $cart,
        Context $context,
        ArrayUtils $arrayUtils,
        Item $wishlistItem,
        CatalogSession $catalogSession,
        array $data = []
    ) {
        $this->configHelper = $configHelper;
        $this->urlHelper = $urlHelper;
        $this->sessionHelper = $sessionHelper;
        $this->draftFactory = $draftFactory;
        $this->cart = $cart;
        $this->_isScopePrivate = true; //@todo remove?
        $this->wishlistItem = $wishlistItem;
        $this->_catalogSession = $catalogSession;

        parent::__construct($context, $arrayUtils, $data);

        $this->draftMasterId = $this->_catalogSession->getData('printformer_masterid');
    }

    /**
     * {@inheritdoc}
     */
    protected function _toHtml()
    {
        if (!$this->getProduct() || ($this->isPrintformerEnabled() == '1' && $this->getProduct()->isSaleable())) {
            return parent::_toHtml();
        }
        return '';
    }

    /**
     * @return bool
     */
    public function isPrintformerEnabled()
    {
        return $this->getProduct()->getPrintformerEnabled();
    }

    public function getPersonalisations()
    {
        if ($this->getRequest()->getActionName() == 'configure'
            && $this->getRequest()->getParam('id')
            && $this->getRequest()->getParam('product_id')
        ) {
            $quoteItem = null;
            $id        = (int)$this->getRequest()->getParam('id');
            $productId = (int)$this->getRequest()->getParam('product_id');
            if ($id) {
                $quoteItem = $this->cart->getQuote()->getItemById($id);
                if ($quoteItem && $productId == $quoteItem->getProduct()->getId()) {
                    $buyRequest = $quoteItem->getBuyRequest();
                    return $buyRequest->getData('printformer_personalisations');
                }
            }
        } else {
            $catalogSession = $this->sessionHelper->getCatalogSession();
            $personalisations = $catalogSession->getData(Save::PERSONALISATIONS_QUERY_PARAM);
            if (isset($personalisations[$this->getProduct()->getStoreId()][$this->getProduct()->getId()])) {
                return $personalisations[$this->getProduct()->getStoreId()][$this->getProduct()->getId()];
            }
        }

        return null;
    }

    /**
     * @return int
     */
    public function getDraftId()
    {
        $draftId = null;
        // Get draft ID on cart product edit page
        if ($this->getRequest()->getActionName() == 'configure'
            && $this->getRequest()->getParam('id')
            && $this->getRequest()->getParam('product_id')
        ) {
            $quoteItem = null;
            $wishlistItem = null;
            $id        = (int)$this->getRequest()->getParam('id');
            $productId = (int)$this->getRequest()->getParam('product_id');
            if ($id) {
                switch ($this->getRequest()->getModuleName()) {
                    case 'checkout':
                        $quoteItem = $this->cart->getQuote()->getItemById($id);
                        if ($quoteItem && $productId == $quoteItem->getProduct()->getId()) {
                            $draftId = $quoteItem->getData(InstallSchema::COLUMN_NAME_DRAFTID);
                        }
                        break;
                    case 'wishlist':
                        $wishlistItem = $this->wishlistItem->loadWithOptions($id);
                        if ($wishlistItem && $productId == $wishlistItem->getProductId()) {
                            $draftId = $wishlistItem->getOptionByCode(InstallSchema::COLUMN_NAME_DRAFTID)->getValue();
                        }
                        break;
                    default:
                        break;
                }
            }
        } else {
            $sessionUniqueId = $this->sessionHelper->getCustomerSession()->getSessionUniqueID();
            if($sessionUniqueId)
            {
                $uniqueIdExplode = explode(':', $sessionUniqueId);
                if(isset($uniqueIdExplode[1]) && $uniqueIdExplode[1] == $this->getProduct()->getId())
                {
                    /** @var Draft $draft */
                    $draft = $this->draftFactory->create();
                    $draftCollection = $draft->getCollection()
                        ->addFieldToFilter('session_unique_id', ['eq' => $sessionUniqueId])
                        ->setOrder('created_at', AbstractDb::SORT_ORDER_DESC);

                    if ($draftCollection->count() > 0)
                    {
                        $draft = $draftCollection->getFirstItem();
                        if ($draft->getId() && $draft->getDraftId())
                        {

                            $draftId = $draft->getDraftId();
                        }
                    }
                }
            }
        }

        return $draftId;
    }

    /**
     * @return int
     */
    public function getIntent()
    {
        $intent = null;
        // Get draft ID on cart product edit page
        if ($this->getRequest()->getActionName() == 'configure'
            && $this->getRequest()->getParam('id')
            && $this->getRequest()->getParam('product_id')
        ) {
            $quoteItem = null;
            $wishlistItem = null;
            $id        = (int)$this->getRequest()->getParam('id');
            $productId = (int)$this->getRequest()->getParam('product_id');
            if ($id) {
                switch ($this->getRequest()->getModuleName()) {
                    case 'checkout':
                        $quoteItem = $this->cart->getQuote()->getItemById($id);
                        if ($quoteItem && $productId == $quoteItem->getProduct()->getId()) {
                            $buyRequest = $quoteItem->getBuyRequest();
                            $intent = $buyRequest->getData(InstallSchema::COLUMN_NAME_INTENT);
                        }
                        break;
                    case 'wishlist':
                        $wishlistItem = $this->wishlistItem->loadWithOptions($id);
                        if ($wishlistItem && $productId == $wishlistItem->getProductId()) {
                            $buyRequest = $wishlistItem->getBuyRequest();
                            $intent = $buyRequest->getData(InstallSchema::COLUMN_NAME_INTENT);
                        }
                        break;
                    default:
                        break;
                }
            }
        } else {
            $intent = $this->sessionHelper->getCurrentIntent();
        }

        return $intent;
    }

    /**
     * @return int
     */
    public function getMasterId()
    {
        return $this->getProduct()->getPrintformerProduct();
    }

    /**
     * @param string $intent
     *
     * @return string
     */
    public function getEditorUrl($intent = null)
    {
        $editParams = [];
        if($this->isOnConfigurePDS()) {
            $editParams = [
                'quote_id' => $this->getRequest()->getParam('id'),
                'product_id' => $this->getRequest()->getParam('product_id')
            ];
        }
        return $this->urlHelper
            ->getEditorEntry($this->getProduct()->getId(), $this->getMasterId(), $this->getDraftId(), $editParams, $intent);
    }

    /**
     * @return boolean
     */
    public function isAllowSkipConfig()
    {
        return $this->configHelper
            ->isAllowSkipConfig();
    }

    /**
     * @return string
     */
    public function getButtonText()
    {
        return $this->configHelper
            ->getButtonText();
    }

    /**
     * @return string
     */
    public function getButtonCss()
    {
        return $this->configHelper
            ->getButtonCss();
    }

    /**
     * @boolean
     */
    public function isFormatChangeNotice()
    {
        return $this->configHelper->isFormatChangeNotice();
    }

    /**
     * @return string
     */
    public function getFormatNoticeText()
    {
        return $this->configHelper->getFormatNoticeText();
    }

    public function getCloseNoticeText()
    {
        return $this->configHelper->getCloseNoticeText();
    }

    /**
     * @return string
     */
    public function getRedirectType()
    {
        return $this->configHelper->getConfigRedirect();
    }

    /**
     * @return string
     */
    public function getVariationsConfig()
    {
        $config = [];
        if ($this->getFormatAttributeId()) {
            $id = 'attribute' . $this->getFormatAttributeId();
            $config[$id] = [
                'param'  => $this->getFormatQueryParameter(),
                'map'    => $this->getFormatAttributeConfig(),
                'notice' => $this->isFormatChangeNotice()
            ];
        }
        if ($this->getFormatOptionId()) {
            $id = 'select_' . $this->getFormatOptionId();
            $config[$id] = [
                'param'  => $this->getFormatQueryParameter(),
                'map'    => $this->getFormatOptionConfig(),
                'notice' => $this->isFormatChangeNotice()
            ];
        }
        if ($this->getColorAttributeId()) {
            $id = 'attribute' . $this->getColorAttributeId();
            $config[$id] = [
                'param'  => $this->getColorQueryParameter(),
                'map'    => $this->getColorAttributeConfig(),
                'notice' => false
            ];
        }
        if ($this->getColorOptionId()) {
            $id = 'select_' . $this->getColorOptionId();
            $config[$id] = [
                'param'  => $this->getColorQueryParameter(),
                'map'       => $this->getColorOptionConfig(),
                'notice' => false
            ];
        }

        return $config;
    }

    /**
     * @return string
     */
    public function getFormatQueryParameter()
    {
        return $this->configHelper->getFormatQueryParameter();
    }

    /**
     * @return bool
     */
    public function isFormatAttributeEnabled()
    {
        return $this->configHelper->isFormatAttributeEnabled();
    }

    /**
     * @return string
     */
    public function getFormatAttributeId()
    {
        if (!$this->isFormatAttributeEnabled()) {
            return null;
        }
        $attributeCode = $this->configHelper->getFormatAttributeName();
        foreach ($this->getProduct()->getAttributes() as $attribute) {
            if ($attribute->getAttributeCode() == $attributeCode) {
                return $attribute->getId();
            }
        }
        return null;
    }

    /**
     * @return string
     */
    public function getFormatAttributeName()
    {
        return $this->configHelper->getFormatAttributeName();
    }

    /**
     * @return array|null
     */
    public function getFormatAttributeConfig()
    {
        if (!$this->isFormatAttributeEnabled()) {
            return null;
        }
        $config = [];
        $configValues = $this->configHelper->getFormatAttributeValues();
        foreach ($configValues as $configValue) {
            $config[$configValue['attr_id']] = $configValue['value'];
        }
        return $config;
    }

    /**
     * @return bool
     */
    public function isFormatOptionEnabled()
    {
        return $this->configHelper->isFormatOptionEnabled();
    }

    /**
     * @return string
     */
    public function getFormatOptionId()
    {
        if (!$this->isFormatOptionEnabled()) {
            return null;
        }
        $optionName = $this->configHelper->getFormatOptionName();
        foreach ($this->getProduct()->getOptions() as $option) {
            if (strcasecmp($option->getDefaultTitle(), $optionName) === 0) {
                return $option->getId();
            }
        }
        return null;
    }

    /**
     * @return string
     */
    public function getFormatOptionName()
    {
        return $this->configHelper->getFormatOptionName();
    }

    /**
     * @return bool
     */
    public function getFormatOptionConfig()
    {
        if (!$this->isFormatOptionEnabled()) {
            return null;
        }
        $config = [];
        $optionName = $this->configHelper->getFormatOptionName();
        foreach ($this->getProduct()->getOptions() as $option) {
            if ($option->getDefaultTitle() != $optionName) {
                continue;
            }
            $configValues = $this->configHelper->getFormatOptionValues();
            foreach ($option->getValues() as $valueId => $value) {
                foreach ($configValues as $configValue) {
                    if ($value->getDefaultTitle() != $configValue['option']) {
                        continue;
                    }
                    $config[$valueId] = $configValue['value'];
                }
            }
        }
        return $config;
    }

    /**
     * @return string
     */
    public function getColorQueryParameter()
    {
        return $this->configHelper->getColorQueryParameter();
    }

    /**
     * @return bool
     */
    public function isColorAttributeEnabled()
    {
        return $this->configHelper->isColorAttributeEnabled();
    }

    /**
     * @return string
     */
    public function getColorAttributeId()
    {
        if (!$this->isColorAttributeEnabled()) {
            return null;
        }
        $attributeCode = $this->configHelper->getColorAttributeName();
        foreach ($this->getProduct()->getAttributes() as $attribute) {
            if ($attribute->getAttributeCode() == $attributeCode) {
                return $attribute->getId();
            }
        }
        return null;
    }

    /**
     * @return string
     */
    public function getColorAttributeName()
    {
        return $this->configHelper->getColorAttributeName();
    }

    /**
     * @return array|null
     */
    public function getColorAttributeConfig()
    {
        if (!$this->isColorAttributeEnabled()) {
            return null;
        }
        $config = [];
        $configValues = $this->configHelper->getColorAttributeValues();
        foreach ($configValues as $configValue) {
            $config[$configValue['attr_id']] = $configValue['value'];
        }
        return $config;
    }

    /**
     * @return bool
     */
    public function isColorOptionEnabled()
    {
        return $this->configHelper->isColorOptionEnabled();
    }

    /**
     * @return string
     */
    public function getColorOptionId()
    {
        if (!$this->isColorOptionEnabled()) {
            return null;
        }
        $optionName = $this->configHelper->getColorOptionName();
        foreach ($this->getProduct()->getOptions() as $option) {
            if (strcasecmp($option->getDefaultTitle(), $optionName) === 0) {
                return $option->getId();
            }
        }
        return null;
    }

    /**
     * @return string
     */
    public function getColorOptionName()
    {
        return $this->configHelper->getColorOptionName();
    }

    /**
     * @return array|null
     */
    public function getColorOptionConfig()
    {
        if (!$this->isColorOptionEnabled()) {
            return null;
        }
        $config = [];
        $optionName = $this->configHelper->getColorOptionName();
        foreach ($this->getProduct()->getOptions() as $option) {
            if ($option->getDefaultTitle() != $optionName) {
                continue;
            }
            $configValues = $this->configHelper->getColorOptionValues();
            foreach ($option->getValues() as $valueId => $value) {
                foreach ($configValues as $configValue) {
                    if ($value->getDefaultTitle() != $configValue['option']) {
                        continue;
                    }
                    $config[$valueId] = $configValue['value'];
                }
            }
        }
        return $config;
    }

    /**
     * @param integer $draftId
     * @return array
     */
    public function getProductVariations($draftId = null)
    {
        $variations = [];
        if ($draftId) {
            $draft = $this->draftFactory->create()
                ->load($draftId, 'draft_id');
            if ($draft->getFormatVariation()) {
                $variations[$this->configHelper->getFormatQueryParameter()] = $draft->getFormatVariation();
            }
            if ($draft->getColorVariation()) {
                $variations[$this->configHelper->getColorQueryParameter()] = $draft->getColorVariation();
            }
        }
        return $variations;
    }

    /**
     * @param integer $draftId
     * @return array
     */
    public function getProductQty($draftId = null)
    {
        $qty = 1; //@todo min qty from sysconf?
        if ($draftId) {
            $draft = $this->draftFactory->create() //@todo add getDraft()
                ->load($draftId, 'draft_id');
            if ($draft->getQty()) {
                $qty = $draft->getQty();
            }
        }
        return $qty;
    }

    public function getUniqueSessionId()
    {
        $uniqueId = null;

        if ($this->getRequest()->getActionName() == 'configure'
            && $this->getRequest()->getParam('id')
            && $this->getRequest()->getParam('product_id')
        ) {
            $quoteItem = null;
            $wishlistItem = null;
            $id        = (int)$this->getRequest()->getParam('id');
            $productId = (int)$this->getRequest()->getParam('product_id');
            if ($id) {
                switch ($this->getRequest()->getModuleName()) {
                    case 'checkout':
                        $quoteItem = $this->cart->getQuote()->getItemById($id);
                        if ($quoteItem && $productId == $quoteItem->getProduct()->getId()) {
                            $buyRequest = $quoteItem->getBuyRequest();
                            $uniqueId = $buyRequest->getData('printformer_unique_session_id');
                            $this->sessionHelper->getCustomerSession()->setSessionUniqueID($uniqueId);
                        }
                        break;
                    case 'wishlist':
                        $wishlistItem = $this->wishlistItem->loadWithOptions($id);
                        if ($wishlistItem && $productId == $wishlistItem->getProductId()) {
                            $buyRequest = $wishlistItem->getBuyRequest();
                            $uniqueId = $buyRequest->getData('printformer_unique_session_id');
                            $this->sessionHelper->getCustomerSession()->setSessionUniqueID($uniqueId);
                        }
                        break;
                    default:
                        break;
                }
            }
        }
        else
        {
            $uniqueId = $this->sessionHelper->getCustomerSession()->getSessionUniqueID();
        }

        return $uniqueId;
    }

    /**
     * @return string
     */
    public function getJsonConfig()
    {
        $uniqueId = $this->getUniqueSessionId();
        $uniqueIdExplode = explode(':', $uniqueId);
        if(isset($uniqueIdExplode[1]) && $uniqueIdExplode[1] != $this->getProduct()->getId())
        {
            $uniqueId = null;
        }
        $config = [
            'qtySelector'           => '#qty',
            'addBtnSelector'        => '#product-addtocart-button, #product-updatecart-button',
            'editBtnSelector'       => '#printformer-edit-button',
            'uploadBtnSelector'     => '#printformer-upload-button',
            'editorMainSelector'    => '#printformer-editor-main',
            'editorCloseSelector'   => '#printformer-editor-close',
            'editorNoticeSelector'  => '#printformer-editor-notice',
            'draftId'               => $this->getDraftId(),
            'intent'                => $this->getIntent(),
            'unique_id'             => $uniqueId,
            'productTitle'          => $this->getProduct()->getName(),
            'allowAddCart'          => $this->isAllowSkipConfig() || $this->getDraftId(),
            'urls' => [
                'customize' => $this->getEditorUrl('customize'),
                'personalize' => $this->getEditorUrl('personalize'),
                'upload' => $this->getEditorUrl('upload'),
                'uploadAndEditor' => $this->getEditorUrl('upload-and-editor')
            ],
            'variationsConfig' => $this->getVariationsConfig(),
            'variations'       => $this->getProductVariations($this->getDraftId()),
            'qty'              => $this->getProductQty($this->getDraftId())
        ];

        $extendConfig = [
            'IsUploadProduct' => $this->isUploadProduct(),
            'DraftsGetUrl' => $this->getDraftsGetUrl(),
            'ProductId' => $this->getProduct()->getId(),
            'isConfigure' => $this->isOnConfigurePDS(),
            'draftMasterId' => $this->getDraftMasterId(),
        ];

        $extendConfig['currentSessionIntent'] = $this->getIntent();

        $extendConfig[Save::PERSONALISATIONS_QUERY_PARAM] = 0;
        if($this->getPersonalisations())
        {
            $extendConfig[Save::PERSONALISATIONS_QUERY_PARAM] = $this->getPersonalisations();
            if ($this->isOnConfigurePDS()) {
                $extendConfig[Save::PERSONALISATIONS_QUERY_PARAM . '_conf'] = true;
            } else {
                $extendConfig[Save::PERSONALISATIONS_QUERY_PARAM . '_conf'] = false;
            }
        }

        return json_encode(array_merge($config, $extendConfig));
    }

    /**
     * @return string
     */
    public function getDraftMasterId()
    {
        if (!$this->draftMasterId
            && $this->getRequest()->getActionName() == 'configure'
            && $this->getRequest()->getParam('id')
            && $this->getRequest()->getParam('product_id')
        ) {
            $quoteItem = null;
            $id        = (int)$this->getRequest()->getParam('id');
            $productId = (int)$this->getRequest()->getParam('product_id');
            if ($id) {
                $quoteItem = $this->cart->getQuote()->getItemById($id);
            }
            if ($quoteItem && $productId == $quoteItem->getProduct()->getId()) {
                $this->draftMasterId = $quoteItem->getBuyRequest()->getData('printformer_masterid');
            }
        }
        return $this->draftMasterId;
    }

    /**
     * @return string
     */
    public function getUploadEditorUrl()
    {
        return $this->getEditorUrl('upload-and-editor');
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return \Magento\Framework\DataObject
     */
    public function getCapabilities(CatalogProduct $product)
    {
        if(!$this->_capabilities) {
            $obj = new DataObject();
            $attribute = $product->getResource()->getAttribute('printformer_capabilities');
            $capabilities = explode(', ', $attribute->getFrontend()->getValue($product));

            foreach ($capabilities as $value) {
                $obj->setData(strtolower(str_replace(' ', '_', $value)), true);
            }
            $this->_capabilities = $obj;
        }

        return $this->_capabilities;
    }

    /**
     * @return bool
     */
    public function isEditorProduct()
    {
        $capabilities = $this->getCapabilities($this->getProduct());

        return $capabilities->getEditor();
    }

    /**
     * @return bool
     */
    public function isPersonalizationProduct()
    {
        $capabilities = $this->getCapabilities($this->getProduct());

        return $capabilities->getPersonalizations();
    }

    /**
     * @return bool
     */
    public function isUploadProduct()
    {
        $capabilities = $this->getCapabilities($this->getProduct());

        return $capabilities->getUpload();
    }

    public function isUploadAndEditorProduct()
    {
        $capabilities = $this->getCapabilities($this->getProduct());

        return $capabilities->getUploadAndEditor();
    }

    /**
     * @return string
     */
    public function getDraftsGetUrl()
    {
        return $this->_urlBuilder->getUrl('printformer/get/draft');
    }

    /**
     * @return bool
     */
    public function isOnConfigurePDS()
    {
        $_request = $this->getRequest();
        $isConfigure = false;
        if(
            $_request->getModuleName() == 'checkout' &&
            $_request->getActionName() == 'configure'
        )
        {
            $isConfigure = true;
        }

        return $isConfigure;
    }

    public function getPreselection()
    {
        $product = $this->getProduct();
        if (!$product) {
            return '{}';
        }
        $printformerData = json_decode($this->getJsonConfig(), true);
        $catalogSession = $this->sessionHelper->getCatalogSession();
        $preselectData = $catalogSession->getSavedPrintformerOptions();
        if($product->getId() != $preselectData['product']) {
            $preselectData = [];
        }
        if(!empty($preselectData) || $this->isOnConfigurePDS()) {
            if ($this->isOnConfigurePDS()) {
                $printformerData['preselection'] = $this->getConfigurePreselection();
            } else {
                $printformerData['preselection'] = $preselectData;
            }
        }
        $printformerData['openControllerPreselect'] = true;

        return json_encode($printformerData);
    }

    /**
     * @return array
     */
    public function getConfigurePreselection()
    {
        $request = $this->getRequest();
        if($request->getParam('id')) {
            /** @var Item $quoteItem */
            $quoteItem = ObjectManager::getInstance()->create('Magento\Quote\Model\Quote\Item');
            $quoteItem->getResource()->load($quoteItem, (int)$request->getParam('id'));
            $connection = $quoteItem->getResource()->getConnection();

            if($quoteItem->getId()) {
                $result = $connection->fetchRow("
                    SELECT * FROM `" . $connection->getTableName('quote_item_option') . "`
                    WHERE
                        `item_id` = " . $quoteItem->getId() . " AND
                        `product_id` = " . $request->getParam('product_id') . " AND
                        `code` = 'info_buyRequest'
                ");
                $objm = ObjectManager::getInstance();
                /** @var \Magento\Framework\App\ProductMetadataInterface $productMeta */
                $productMeta = $objm->get('Magento\Framework\App\ProductMetadataInterface');
                if(version_compare($productMeta->getVersion(), '2.2.1', '>=')) {
                    $buyRequest = new DataObject(json_decode($result['value'], true));
                } else {
                    $buyRequest = new DataObject(unserialize($result['value']));
                }
                // $buyRequest = $quoteItem->getBuyRequest();

                $product = $buyRequest->getProduct();
                $options = $buyRequest->getOptions();
                $qty = $quoteItem->getQty();

                $_returnJson = [];

                if (is_array($options)) {
                    foreach ($options as $key => $_option) {
                        $_returnJson['options'][$key]['value'] = $_option;
                    }
                }

                $_returnJson['product'] = $product;
                $_returnJson['qty']['value'] = $qty;

                $jsonDataObject = new DataObject($_returnJson);
                $this->_eventManager->dispatch(
                    'printformer_get_preselection_after',
                    ['connection' => $connection, 'options' => $options, 'json_data_object' => $jsonDataObject]
                );
                $_returnJson = $jsonDataObject->getData();

                return $_returnJson;
            }
        }

        return [];
    }
}
