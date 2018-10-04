<?php

namespace Rissc\Printformer\Block\Catalog\Product\View;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\View\AbstractView;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Session as CatalogSession;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Quote\Model\Quote;
use Magento\Wishlist\Model\Item;
use Rissc\Printformer\Controller\Editor\Save;
use Rissc\Printformer\Helper\Api\Url;
use Rissc\Printformer\Helper\Config;
use Rissc\Printformer\Helper\Session;
use Rissc\Printformer\Model\Draft;
use Rissc\Printformer\Model\DraftFactory;
use Rissc\Printformer\Model\Product as PrintformerProduct;
use Rissc\Printformer\Setup\InstallSchema;
use Rissc\Printformer\Helper\Product as PrintformerProductHelper;
use Rissc\Printformer\Helper\Api as ApiHelper;

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
     * @var PrintformerProductHelper
     */
    protected $printformerProductHelper;

    /**
     * @var ApiHelper
     */
    protected $_apiHelper;

    /**
     * Printformer constructor.
     *
     * @param Config         $configHelper
     * @param Url            $urlHelper
     * @param Session        $sessionHelper
     * @param DraftFactory   $draftFactory
     * @param Cart           $cart
     * @param Context        $context
     * @param ArrayUtils     $arrayUtils
     * @param Item           $wishlistItem
     * @param CatalogSession $catalogSession
     * @param ApiHelper      $apiHelper
     * @param array          $data
     */
    public function __construct(
        PrintformerProductHelper $printformerProductHelper,
        Config $configHelper,
        Url $urlHelper,
        Session $sessionHelper,
        DraftFactory $draftFactory,
        Cart $cart,
        Context $context,
        ArrayUtils $arrayUtils,
        Item $wishlistItem,
        CatalogSession $catalogSession,
        ApiHelper $apiHelper,
        array $data = []
    )
    {
        $this->printformerProductHelper = $printformerProductHelper;
        $this->configHelper = $configHelper;
        $this->urlHelper = $urlHelper;
        $this->sessionHelper = $sessionHelper;
        $this->draftFactory = $draftFactory;
        $this->cart = $cart;
        $this->_isScopePrivate = true; //@todo remove?
        $this->wishlistItem = $wishlistItem;
        $this->_catalogSession = $catalogSession;
        $this->_apiHelper = $apiHelper;

        parent::__construct($context, $arrayUtils, $data);

        $this->draftMasterId = $this->_catalogSession->getData('printformer_masterid');
    }

    /**
     * {@inheritdoc}
     */
    protected function _toHtml()
    {
        if (!$this->getProduct() || ($this->getProduct()->isSaleable())) {
            return parent::_toHtml();
        }

        return '';
    }

    public function getPersonalisations()
    {
        if ($this->getRequest()->getActionName() == 'configure' && $this->getRequest()->getParam('id') && $this->getRequest()->getParam('product_id')) {
            $quoteItem = null;
            $id = (int)$this->getRequest()->getParam('id');
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
    public function getDraftId(PrintformerProduct $printformerProduct)
    {
        $draftId = null;
        // Get draft ID on cart product edit page
        if ($this->getRequest()->getActionName() == 'configure' && $this->getRequest()->getParam('id') && $this->getRequest()->getParam('product_id')) {
            $quoteItem = null;
            $wishlistItem = null;
            $id = (int)$this->getRequest()->getParam('id');
            $productId = (int)$this->getRequest()->getParam('product_id');
            if ($id) {
                switch ($this->getRequest()->getModuleName()) {
                    case 'checkout':
                        $quoteItem = $this->cart->getQuote()->getItemById($id);
                        if ($quoteItem && $productId == $quoteItem->getProduct()->getId()) {
                            $buyRequest = $quoteItem->getBuyRequest();
                            $draftHashRelations = $buyRequest->getDraftHashRelations();
                            if (isset($draftHashRelations[$printformerProduct->getId()])) {
                                $draftId = $draftHashRelations[$printformerProduct->getId()];
                            }
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
            if ($sessionUniqueId) {
                $uniqueIdExplode = explode(':', $sessionUniqueId);
                if (isset($uniqueIdExplode[1]) && $uniqueIdExplode[1] == $this->getProduct()->getId()) {
                    /** @var Draft $draft */
                    $draft = $this->draftFactory->create();
                    $draftCollection = $draft->getCollection()
                        ->addFieldToFilter('session_unique_id', ['eq' => $sessionUniqueId])
                        ->addFieldToFilter('printformer_product_id', $printformerProduct->getId())
                        ->setOrder('created_at', AbstractDb::SORT_ORDER_DESC);

                    if ($draftCollection->count() > 0) {
                        $draft = $draftCollection->getFirstItem();
                        if ($draft->getId() && $draft->getDraftId()) {
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
        if ($this->getRequest()->getActionName() == 'configure' && $this->getRequest()->getParam('id') && $this->getRequest()->getParam('product_id')) {
            $quoteItem = null;
            $wishlistItem = null;
            $id = (int)$this->getRequest()->getParam('id');
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
     * @return array
     */
    public function getCatalogProductPrintformerProducts()
    {
        return $this->printformerProductHelper
            ->getCatalogProductPrintformerProducts(
                $this->getProduct()->getId(),
                $this->_storeManager->getStore()->getId()
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getPrintformerProductsArray(Product $product = null)
    {
        if (!$product) {
            $product = $this->getProduct();
        }

        $printformerProducts = [];

        $i = 0;
        $pfProducts = $this->printformerProductHelper->getPrintformerProducts(
            $product->getId(),
            $this->_storeManager->getStore()->getId()
        );
        foreach($pfProducts as $printformerProduct) {
            $printformerProducts[$i] = $printformerProduct->getData();
            $printformerProducts[$i]['url'] = $this->getEditorUrl($printformerProduct, $product);
            if ($this->canShowDeleteButton($this->getDraftId($printformerProduct))) {
                $printformerProducts[$i]['delete_url'] = $this->getDeleteUrl($printformerProduct, $product->getId());
            }
            $printformerProducts[$i]['draft_id'] = $this->getDraftId($printformerProduct);

            $personalisations = $this->getPersonalisationCount($printformerProducts[$i]['draft_id']);
            if ($personalisations > 1) {
                $printformerProducts[$i]['personalisations'] = $personalisations;
            }
            $i++;
        }

        return $printformerProducts;
    }

    /**
     * @param PrintformerProduct $printformerProduct
     * @param Product $product
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getEditorUrl(PrintformerProduct $printformerProduct, Product $product = null)
    {
        if (!$product) {
            $product = $this->getProduct();
        }

        $params = ['printformer_product_id' => $printformerProduct->getId()];

        if ($this->isOnConfigurePDS()) {
            $params['quote_id'] = $this->getRequest()->getParam('id');
            $params['product_id'] = $this->getRequest()->getParam('product_id');
        }

        return $this->urlHelper->getEditorEntry(
            $product->getId(),
            $printformerProduct->getMasterId(),
            $this->getDraftId($printformerProduct),
            $params,
            $printformerProduct->getIntent()
        );
    }

    /**
     * @param PrintformerProduct $printformerProduct
     * @param int                $productId
     *
     * @return string
     */
    public function getDeleteUrl(PrintformerProduct $printformerProduct, $productId)
    {
        return $this->_urlBuilder->getUrl('printformer/delete/draft', [
            'printformer_product' => $printformerProduct->getId(),
            'intent' => $printformerProduct->getIntent(),
            'product_id' => $productId
        ]);
    }

    /**
     * @return string
     */
    public function getButtonText()
    {
        return $this->configHelper->getButtonText();
    }

    /**
     * @param DataObject $catalogProductPrintformerProduct
     * @return string
     */
    public function getButtonCss($catalogProductPrintformerProduct)
    {
        $additionalCss = new DataObject();
        $this->_eventManager->dispatch('printformer_product_button_css', [
            'block' => $this,
            'catalog_product_printformer_product' => $catalogProductPrintformerProduct,
            'additional_css' => $additionalCss
        ]);
        return $this->configHelper->getButtonCss() . ' ' . $additionalCss->getValue();
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
            $config[$id] = ['param' => $this->getFormatQueryParameter(), 'map' => $this->getFormatAttributeConfig(), 'notice' => $this->isFormatChangeNotice()];
        }
        if ($this->getFormatOptionId()) {
            $id = 'select_' . $this->getFormatOptionId();
            $config[$id] = ['param' => $this->getFormatQueryParameter(), 'map' => $this->getFormatOptionConfig(), 'notice' => $this->isFormatChangeNotice()];
        }
        if ($this->getColorAttributeId()) {
            $id = 'attribute' . $this->getColorAttributeId();
            $config[$id] = ['param' => $this->getColorQueryParameter(), 'map' => $this->getColorAttributeConfig(), 'notice' => false];
        }
        if ($this->getColorOptionId()) {
            $id = 'select_' . $this->getColorOptionId();
            $config[$id] = ['param' => $this->getColorQueryParameter(), 'map' => $this->getColorOptionConfig(), 'notice' => false];
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
     *
     * @return array
     */
    public function getProductVariations($draftId = null)
    {
        $variations = [];
        if ($draftId) {
            $draft = $this->draftFactory->create()->load($draftId, 'draft_id');
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
     *
     * @return array
     */
    public function getProductQty($draftId = null)
    {
        $qty = 1; //@todo min qty from sysconf?
        if ($draftId) {
            $draft = $this->draftFactory->create()//@todo add getDraft()
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

        if ($this->getRequest()->getActionName() == 'configure' && $this->getRequest()->getParam('id') && $this->getRequest()->getParam('product_id')) {
            $quoteItem = null;
            $wishlistItem = null;
            $id = (int)$this->getRequest()->getParam('id');
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
        } else {
            $uniqueId = $this->sessionHelper->getCustomerSession()->getSessionUniqueID();
        }

        return $uniqueId;
    }

    /**
     * @return string
     */
    public function getJsonConfig()
    {
        $product = $this->getProduct();
        if (!$product) {
            return '{}';
        }

        $uniqueId = $this->getUniqueSessionId();
        $uniqueIdExplode = explode(':', $uniqueId);
        if (isset($uniqueIdExplode[1]) && $uniqueIdExplode[1] != $this->getProduct()->getId()) {
            $uniqueId = null;
        }
        $config = [
            'qtySelector' => '#qty',
            'buttonSelector' => '#printformer-button-',
            'addBtnSelector' => '#product-addtocart-button, #product-updatecart-button',
            'editorMainSelector' => '#printformer-editor-main',
            'editorCloseSelector' => '#printformer-editor-close',
            'editorNoticeSelector' => '#printformer-editor-notice',
            'uniqueId' => $uniqueId,
            'productTitle' => $this->getProduct()->getName(),
            'allowSkipConfig' => $this->configHelper->isAllowSkipConfig(), //@todo || $this->getDraftId(),
            'printformerProducts' => $this->getPrintformerProductsArray(),
            'variationsConfig' => $this->getVariationsConfig(),
            'variations' => [], //@todo $this->getProductVariations($this->getDraftId()),
            'qty' => 1, //@todo $this->getProductQty($this->getDraftId()),
            'ProductId' => $this->getProduct()->getId(),
            'isConfigure' => $this->isOnConfigurePDS(),
            'draftMasterId' => $this->getDraftMasterId(),
            'currentSessionIntent' => $this->getIntent(),
            Save::PERSONALISATIONS_QUERY_PARAM => 0,
            Save::PERSONALISATIONS_QUERY_PARAM . '_conf' => false,
            'preselection' => [],
            'openControllerPreselect' => true
        ];

        if ($this->getPersonalisations()) {
            $config[Save::PERSONALISATIONS_QUERY_PARAM] = $this->getPersonalisations();
            if ($this->isOnConfigurePDS()) {
                $config[Save::PERSONALISATIONS_QUERY_PARAM . '_conf'] = true;
            }
        }

        $catalogSession = $this->sessionHelper->getCatalogSession();
        $preselectData = $catalogSession->getSavedPrintformerOptions();
        if ($product->getId() != $preselectData['product']) {
            $preselectData = [];
        }
        if (!empty($preselectData) || $this->isOnConfigurePDS()) {
            if ($this->isOnConfigurePDS()) {
                $config['preselection'] = $this->getConfigurePreselection();
            } else {
                $config['preselection'] = $preselectData;
            }
        }

        return json_encode($config);
    }

    /**
     * @return string
     */
    public function getDraftMasterId()
    {
        if (!$this->draftMasterId && $this->getRequest()->getActionName() == 'configure' && $this->getRequest()->getParam('id') && $this->getRequest()->getParam('product_id')) {
            $quoteItem = null;
            $id = (int)$this->getRequest()->getParam('id');
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
     * @return bool
     */
    public function isOnConfigurePDS()
    {
        return ($this->getRequest()->getModuleName() == 'checkout' && $this->getRequest()->getActionName() == 'configure');
    }

    /**
     * @return array
     */
    public function getConfigurePreselection()
    {
        $request = $this->getRequest();
        if ($request->getParam('id')) {
            /** @var Item $quoteItem */
            $quoteItem = ObjectManager::getInstance()->create('Magento\Quote\Model\Quote\Item');
            $quoteItem->getResource()->load($quoteItem, (int)$request->getParam('id'));
            $connection = $quoteItem->getResource()->getConnection();

            if ($quoteItem->getId()) {
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
                if (version_compare($productMeta->getVersion(), '2.2.1', '>=')) {
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
                $this->_eventManager->dispatch('printformer_get_preselection_after', ['connection' => $connection, 'options' => $options, 'json_data_object' => $jsonDataObject]);
                $_returnJson = $jsonDataObject->getData();

                return $_returnJson;
            }
        }

        return [];
    }

    /**
     * @return void
     */
    public function cleanupDraftSession()
    {
        if ($this->isOnConfigurePDS()) {
            return;
        }

        /*
         * @todo
        if ($this->isDraftInCart($this->getDraftId())) {
            $this->sessionHelper->getCatalogSession()->setSavedPrintformerOptions(null);
            $this->sessionHelper->getCatalogSession()->setData(Save::PERSONALISATIONS_QUERY_PARAM, null);
            $this->sessionHelper->getCatalogSession()->setData(Session::SESSION_KEY_PRINTFORMER_CURRENT_INTENT, null);
            $this->sessionHelper->getCustomerSession()->setSessionUniqueID(null);
        }
        */
    }

    /**
     * @param $draftHash
     *
     * @return bool
     */
    protected function isDraftInCart($draftHash)
    {
        /** @var Quote $quote */
        $quote = $this->cart->getQuote();
        /** @var Item $quoteItem */
        foreach ($quote->getAllItems() as $quoteItem) {
            if ($quoteItem->getPrintformerDraftid() == $draftHash) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $draftHash
     *
     * @return int
     */
    public function getPersonalisationCount($draftHash)
    {
        if (!$draftHash) {
            return 0;
        }
        $draftData = $this->_apiHelper->getPrintformerDraft($draftHash);

        return intval($draftData['personalizations']['amount']);
    }

    /**
     * @return bool
     */
    public function canShowDeleteButton()
    {
        if ($this->isOnConfigurePDS()) {
            return false;
        }

        return $this->configHelper->isDeleteButtonEnabled();
    }
}
