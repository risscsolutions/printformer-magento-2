<?php

namespace Rissc\Printformer\Block\Catalog\Product\View;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\View\AbstractView;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Session as CatalogSession;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Checkout\Model\Cart;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Quote\Model\Quote;
use Magento\Wishlist\Model\Item;
use Rissc\Printformer\Controller\Editor\Save;
use Rissc\Printformer\Helper\Api\Url;
use Rissc\Printformer\Helper\Config;
use Rissc\Printformer\Helper\Session;
use Rissc\Printformer\Model\Config\Source\Redirect;
use Rissc\Printformer\Model\Product as PrintformerProduct;
use Rissc\Printformer\Setup\InstallSchema;
use Rissc\Printformer\Helper\Product as PrintformerProductHelper;
use Rissc\Printformer\Helper\Api as ApiHelper;
use Rissc\Printformer\Helper\ConfigurableProduct as ConfigurableProductHelper;
use Psr\Log\LoggerInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    private ProductAttributeRepositoryInterface $productAttributeRepository;

    private ConfigurableProductHelper $configurableProductHelper;

    /**
     * Printformer constructor.
     *
     * @param PrintformerProductHelper $printformerProductHelper
     * @param Config $configHelper
     * @param Url $urlHelper
     * @param Session $sessionHelper
     * @param Cart $cart
     * @param Context $context
     * @param ArrayUtils $arrayUtils
     * @param Item $wishlistItem
     * @param CatalogSession $catalogSession
     * @param ApiHelper $apiHelper
     * @param LoggerInterface $logger
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param array $data
     */
    public function __construct(
        PrintformerProductHelper $printformerProductHelper,
        Config $configHelper,
        Url $urlHelper,
        Session $sessionHelper,
        Cart $cart,
        Context $context,
        ArrayUtils $arrayUtils,
        Item $wishlistItem,
        CatalogSession $catalogSession,
        ApiHelper $apiHelper,
        LoggerInterface $logger,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        ConfigurableProductHelper $configurableProductHelper,
        array $data = []
    )
    {
        $this->printformerProductHelper = $printformerProductHelper;
        $this->configHelper = $configHelper;
        $this->urlHelper = $urlHelper;
        $this->sessionHelper = $sessionHelper;
        $this->cart = $cart;
        $this->wishlistItem = $wishlistItem;
        $this->_catalogSession = $catalogSession;
        $this->_apiHelper = $apiHelper;
        $this->logger = $logger;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->configurableProductHelper = $configurableProductHelper;

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
     * Get Draft id depends by your request / position from where request is sent.
     *
     * @return int
     */
    public function getDraftId(PrintformerProduct $printformerProduct)
    {
        $draftId = null;
        $productId = $this->getRequest()->getParam('product_id');
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
                            if ($quoteItem->getProductType() === $this->configHelper::CONFIGURABLE_TYPE_CODE) {
                                $children = $quoteItem->getChildren();
                                if (!empty($children)) {
                                    $firstChild = $children[0];
                                    if (!empty($firstChild)) {
                                        $draftId = $this->configHelper->loadDraftFromQuoteItem($firstChild, $printformerProduct->getProductId(), $printformerProduct->getId());
                                    }
                                }
                            } else {
                                $draftId = $this->configHelper->loadDraftFromQuoteItem($quoteItem, $printformerProduct->getProductId(), $printformerProduct->getId());
                            }
                        }
                        break;
                    case 'wishlist':
                        $wishlistItem = $this->wishlistItem->loadWithOptions($id);
                        if ($wishlistItem && $productId == $wishlistItem->getProductId()) {
                            //todo?: change logic to create/get/load correct draft for correct draft-id * maybe complete logic in helper
                            $draftId = $wishlistItem->getOptionByCode(InstallSchema::COLUMN_NAME_DRAFTID)->getValue();
                        }
                        //todo?: adjust to get draft id for child-products like on function loadDraftFromQuoteItem
                        break;
                    default:
                        break;
                }
            }
        } else {
            $productId = $printformerProduct->getProductId();
            $sessionUniqueId = $this->sessionHelper->getSessionUniqueIdByProductId($productId);
            if ($sessionUniqueId) {
                $uniqueIdExplode = explode(':', $sessionUniqueId ?? '');
                if (isset($uniqueIdExplode[1]) && $uniqueIdExplode[1] == $productId) {
                    $draftId = $this->printformerProductHelper->getDraftId($printformerProduct->getId(), $productId);
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
     *
     *
     * @param $productId
     * @param $storeId
     * @return array
     */
    public function getCatalogProductPrintformerProducts(
        $productId,
        $storeId
    ): array
    {
        $result = [];
        if (isset($productId, $storeId)) {
            $result = $this->printformerProductHelper
                ->prepareCatalogProductPrintformerProductsData(
                    $productId,
                    $storeId
                );
        }

        return $result;
    }

    /**
     * Function where information for correct frontend customization is loaded
     *
     * @param Product|null $product
     * @return array
     */
    public function getPrintformerProductsFrontendConfigurationSource(Product $product = null)
    {
        if (!$product) {
            $product = $this->getProduct();
        }

        $printformerProducts = [];

        if ($product->getTypeId() === $this->configHelper::CONFIGURABLE_TYPE_CODE) {
            $childProducts = $product->getTypeInstance()->getUsedProducts($product);
            $childProductIds = [];
            foreach ($childProducts as $simpleProductKey => $simpleProduct) {
                array_push($childProductIds, $simpleProduct->getEntityId());
            }
            $pfProducts = $this->printformerProductHelper->getPrintformerProductsForFrontendConfigurationLogic(
                $product->getId(),
                $this->_storeManager->getStore()->getId(),
                $childProductIds
            );
        } else {
            $pfProducts = $this->printformerProductHelper->getPrintformerProductsForFrontendConfigurationLogic(
                $product->getId(),
                $this->_storeManager->getStore()->getId()
            );
        }

        foreach ($pfProducts as $printformerProductKey => $printformerProduct) {
            $draftId = $this->getDraftId($printformerProduct);

            $printformerProducts[$printformerProductKey] = $printformerProduct->getData();
            $printformerProducts[$printformerProductKey]['url'] = $this->getEditorUrl($printformerProduct, $product);

            if (isset($draftId)) {
                if ($this->canShowDeleteButton()) {
                    $printformerProducts[$printformerProductKey]['delete_url'] = $this->getDeleteUrl($printformerProduct, $product->getId());
                }
                $printformerProducts[$printformerProductKey]['draft_id'] = $draftId;
                $personalisations = $this->getPersonalisationCount($printformerProducts[$printformerProductKey]['draft_id']);
                if ($personalisations > 1) {
                    $printformerProducts[$printformerProductKey]['personalisations'] = $personalisations;
                }
            }
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
    public function getEditorUrl(
        PrintformerProduct $printformerProduct,
        Product $product = null
    )
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
     * @param int $productId
     *
     * @return string
     */
    public function getDeleteUrl(
        PrintformerProduct $printformerProduct,
        $productId
    )
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

    public function getUniqueSessionId()
    {
        $uniqueId = null;

        $productId = (int)$this->getRequest()->getParam('product_id');

        if ($this->getRequest()->getActionName() == 'configure' && $this->getRequest()->getParam('id') && $productId) {
            $id = (int)$this->getRequest()->getParam('id');
            if ($id) {
                switch ($this->getRequest()->getModuleName()) {
                    case 'checkout':
                        $quoteItem = $this->cart->getQuote()->getItemById($id);
                        if ($quoteItem && $productId == $quoteItem->getProduct()->getId()) {
                            $buyRequest = $quoteItem->getBuyRequest();
                            $uniqueId = $buyRequest->getData('printformer_unique_session_id');

                            if ($quoteItem && $productId == $quoteItem->getProductId()) {
                                $buyRequest = $quoteItem->getBuyRequest();
                                $uniqueId = $buyRequest->getData('printformer_unique_session_id');
                                if ($superAttributes = $buyRequest->getData('super_attribute')) {
                                    $childProduct = $this->configurableProductHelper->getChildProductBySuperAttributes($superAttributes, $buyRequest->getData('product'));

                                    $productId = $childProduct->getId();
                                    $uniqueId = $this->sessionHelper->getSessionUniqueIdByProductId($productId);
                                    if (!isset($uniqueId)) {
                                        $printformerDraftField = $childProduct->getPrintformerDraftid();
                                        if ($printformerDraftField) {
                                            $draftHashArray = explode(',', $printformerDraftField ?? '');
                                            foreach($draftHashArray as $draftHash) {
                                                $uniqueId = $this->sessionHelper->setSessionUniqueIdByProductIdAndDraftId($productId, $draftHash);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        break;
                    case 'wishlist':
                        $wishlistItem = $this->wishlistItem->loadWithOptions($id);
                        if ($wishlistItem && $productId == $wishlistItem->getProductId()) {
                            $buyRequest = $wishlistItem->getBuyRequest();
                            $uniqueId = $buyRequest->getData('printformer_unique_session_id');
                            //todo: test and adjust logic to load draft id by wishlistItem
                            //                            $this->sessionHelper->setSessionUniqueId($productId, $uniqueId, $draftid);
                        }
                        break;
                    default:
                        break;
                }
            }
        } else {
            //todo: check via breakpoint if you have the case to land here and miss a correct product-id
            if (!empty($productId)) {
                $uniqueId = $this->sessionHelper->getSessionUniqueIdByProductId($productId);
            }
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
        $uniqueIdExplode = explode(':', $uniqueId ?? '');
        if (isset($uniqueIdExplode[1]) && $uniqueIdExplode[1] != $this->getProduct()->getId()) {
            $uniqueId = null;
        }

        $minSaleQty = 1;
        try {
            $stockItem = $product->getExtensionAttributes()->getStockItem();
            $minSaleQty = $stockItem->getMinSaleQty();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->logger->error($e->getTraceAsString());
        }

        $config = [
            'qtySelector' => '#qty',
            'buttonSelector' => '#printformer-button-',
            'addBtnSelector' => '#product-addtocart-button, #product-updatecart-button',
            'editorMainSelector' => '#printformer-editor-main',
            'editorCloseSelector' => '#printformer-editor-close',
            'editorNoticeSelector' => '#printformer-editor-notice',
            'displayMode' => $this->configHelper->getDisplayMode(),
            'uniqueId' => $uniqueId,
            'productTitle' => $this->getProduct()->getName(),
            'openEditorPreviewText' => $this->configHelper->getOpenEditorPreviewText(),
            'allowSkipConfig' => $this->configHelper->isAllowSkipConfig(), //@todo || $this->getDraftId(),
            'printformerProducts' => $this->getPrintformerProductsFrontendConfigurationSource(),
            'variationsConfig' => $this->getVariationsConfig(),
            'variations' => [], //@todo $this->getProductVariations($this->getDraftId()),
            'qty' => $minSaleQty,
            'ProductId' => $this->getProduct()->getId(),
            'isConfigure' => $this->isOnConfigurePDS(),
            'draftMasterId' => $this->getDraftMasterId(),
            'currentSessionIntent' => $this->getIntent(),
            Save::PERSONALISATIONS_QUERY_PARAM => 0,
            Save::PERSONALISATIONS_QUERY_PARAM . '_conf' => false,
            'preselection' => [],
            'openControllerPreselect' => true,
            'isAddToCartRedirect' => $this->configHelper->getConfigRedirect() != Redirect::CONFIG_REDIRECT_URL_PRODUCT
        ];

        if ($this->getPersonalisations()) {
            $config[Save::PERSONALISATIONS_QUERY_PARAM] = $this->getPersonalisations();
            if ($this->isOnConfigurePDS()) {
                $config[Save::PERSONALISATIONS_QUERY_PARAM . '_conf'] = true;
            }
        }

        $catalogSession = $this->sessionHelper->getCatalogSession();
        $preselectData = $catalogSession->getSavedPrintformerOptions();
        if (!isset($preselectData['product']) || $product->getId() != $preselectData['product']) {
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
                    //todo: check if allowed_classes is working for
                    $buyRequest = new DataObject(unserialize($result['value'], ['allowed_classes' => false]));
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

    public function getDeleteConfirmText()
    {
        return $this->configHelper->getDeleteConfirmText();
    }

    /**
     * Get attribute code by attribute id
     *
     * @param int $id
     * @return false|string
     */
    public function getAttributeCode(int $id)
    {
        $result = false;
        try {
            $result = $this->productAttributeRepository->get($id)->getAttributeCode();
        } catch (NoSuchEntityException $e) {
        }
        return $result;
    }

    /**
     * @param $mainProduct
     * @return array
     */
    public function getConfigurableAndChildrens($mainProduct)
    {
        if ($mainProduct->getTypeId() === ConfigurableType::TYPE_CODE) {
            $childProducts = $mainProduct->getTypeInstance()->getUsedProducts($mainProduct);
            foreach ($childProducts as $simpleProductKey => $simpleProduct) {
                $_attributes = $mainProduct->getTypeInstance(true)->getConfigurableAttributes($mainProduct);
                $attributesPair = [];
                foreach ($_attributes as $_attribute) {
                    $attributeId = (int)$_attribute->getAttributeId();
                    $attributeCode = $this->getAttributeCode($attributeId);
                    $attributesPair[$attributeId] = (int)$simpleProduct->getData($attributeCode);
                }
                $childProducts[$simpleProductKey]->setData('super_attributes', $attributesPair);
                $allProducts = $childProducts;
                array_unshift($allProducts, $mainProduct);
            }
        } else {
            $allProducts = [];
            $allProducts[] = $mainProduct;
        }

        return $allProducts;
    }
}
