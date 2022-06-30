<?php

namespace Rissc\Printformer\Block\Swatches\Product\Renderer;

use Magento\Swatches\Block\Product\Renderer\Configurable as parentConfigurable;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Helper\Product as CatalogProduct;
use Magento\Catalog\Model\Product\Image\UrlBuilder;
use Magento\ConfigurableProduct\Helper\Data;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Swatches\Helper\Data as SwatchData;
use Magento\Swatches\Helper\Media;
use Magento\Swatches\Model\SwatchAttributesProvider;
use Magento\Wishlist\Model\Item;
use Rissc\Printformer\Helper\Product as PrintformerProductHelper;
use Rissc\Printformer\Helper\Config;
use Rissc\Printformer\Model\Product as PrintformerProduct;
use Rissc\Printformer\Setup\InstallSchema;
use Magento\Checkout\Model\Cart;
use Rissc\Printformer\Helper\Session;
use Magento\Wishlist\Model\Item as WishListItem;


/**
 * Swatch renderer block
 */
class Configurable extends parentConfigurable
{
    private PrintformerProductHelper $printformerProductHelper;
    private Config $configHelper;
    private Session $sessionHelper;
    private Cart $cart;
    private WishListItem $wishlistItem;

    public function __construct(
        Context $context,
        ArrayUtils $arrayUtils,
        EncoderInterface $jsonEncoder,
        Data $helper,
        CatalogProduct $catalogProduct,
        CurrentCustomer $currentCustomer,
        PriceCurrencyInterface $priceCurrency,
        ConfigurableAttributeData $configurableAttributeData,
        SwatchData $swatchHelper,
        Media $swatchMediaHelper,
        PrintformerProductHelper $printformerProductHelper,
        Config $configHelper,
        Session $sessionHelper,
        Cart $cart,
        WishListItem $wishlistItem,
        array $data = [],
        SwatchAttributesProvider $swatchAttributesProvider = null,
        UrlBuilder $imageUrlBuilder = null
    )
    {

        parent::__construct($context, $arrayUtils, $jsonEncoder, $helper, $catalogProduct, $currentCustomer, $priceCurrency, $configurableAttributeData, $swatchHelper, $swatchMediaHelper, $data, $swatchAttributesProvider, $imageUrlBuilder);
        $this->printformerProductHelper = $printformerProductHelper;
        $this->configHelper = $configHelper;
        $this->sessionHelper = $sessionHelper;
        $this->cart = $cart;
        $this->wishlistItem = $wishlistItem;
    }

    /**
     * Path to template file with Swatch renderer.
     */
    const SWATCH_RENDERER_TEMPLATE = 'Rissc_Printformer::swatches/product/view/renderer.phtml';

//    protected function getRendererTemplate()
//    {
//        return $this->isProductHasSwatchAttribute ?
//            self::SWATCH_RENDERER_TEMPLATE : self::CONFIGURABLE_RENDERER_TEMPLATE;
//    }

    /**
     * Return renderer template
     *
     * Template for product with swatches is different from product without swatches
     *
     * @return string
     */
    protected function getRendererTemplate()
    {
        return $this->isProductHasSwatchAttribute() ?
            self::SWATCH_RENDERER_TEMPLATE : self::CONFIGURABLE_RENDERER_TEMPLATE;
    }

    /**
     * Get Draft id depends by your request / position from where request is sent.
     *
     * @return int
     */
    public function getDraft(PrintformerProduct $printformerProduct)
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
                $uniqueIdExplode = explode(':', $sessionUniqueId);
                if (isset($uniqueIdExplode[1]) && $uniqueIdExplode[1] == $productId) {
                    $draftId = $this->printformerProductHelper->getDraftId($printformerProduct->getId(), $productId);
                }
            }
        }

        return $draftId;
    }

    /**
     * @return array|false
     */
    public function getPrintformerProducts()
    {
        $product = $this->getProduct();

        if ($product->getTypeId() === $this->configHelper::CONFIGURABLE_TYPE_CODE) {
            $childProducts = $product->getTypeInstance()->getUsedProducts($product);
            $childProductIds = [];
            foreach ($childProducts as $simpleProduct) {
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

        $resultPrintformerProducts = [];
        foreach ($pfProducts as $pfProduct) {
            if (!isset($resultPrintformerProducts[$pfProduct->getProductId()])) {
                $resultPrintformerProducts[$pfProduct->getProductId()] = $pfProduct->getData();
            }
            $draftId = $this->getDraft($pfProduct);
            if (isset($draftId)) {
                if (!isset($resultPrintformerProducts[$pfProduct->getProductId()]['draft_ids'])) {
                    $resultPrintformerProducts[$pfProduct->getProductId()]['draft_ids'] = [];
                }
                $resultPrintformerProducts[$pfProduct->getProductId()]['draft_ids'][$pfProduct->getId()] = $draftId;
            }
        }

        !empty($resultPrintformerProducts) && is_array($resultPrintformerProducts) ? $result = json_encode($resultPrintformerProducts) : $result = '';

        return $result;
    }
}
