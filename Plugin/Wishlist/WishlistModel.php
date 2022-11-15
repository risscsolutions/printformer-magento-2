<?php

namespace Rissc\Printformer\Plugin\Wishlist;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Wishlist\Model\Wishlist;
use Rissc\Printformer\Helper as Helper;
use Rissc\Printformer\Helper\Session as SessionHelper;
use Rissc\Printformer\Helper\Product as productHelper;
use Rissc\Printformer\Helper\Config as configHelper;

class WishlistModel
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var SessionHelper
     */
    protected $sessionHelper;
    private productHelper $productHelper;
    private configHelper $configHelper;
    private ProductRepositoryInterface $productRepository;

    /**
     * WishlistModel constructor.
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param SessionHelper $sessionHelper
     */
    public function __construct(
        Registry $registry,
        StoreManagerInterface $storeManager,
        SessionHelper $sessionHelper,
        ProductHelper $productHelper,
        ConfigHelper $configHelper,
        ProductRepositoryInterface $productRepository
    ) {
        $this->registry = $registry;
        $this->storeManager = $storeManager;
        $this->sessionHelper = $sessionHelper;
        $this->productHelper = $productHelper;
        $this->configHelper = $configHelper;
        $this->productRepository = $productRepository;
    }

    /**
     * @param Wishlist $subject
     * @param int|Product $product
     * @param null $buyRequest
     * @param bool $forciblySetQty
     */
    public function beforeAddNewItem(
        Wishlist $subject,
        int|ProductModel $product,
        $buyRequest = null,
        $forciblySetQty = false
    ) {
        if (isset($buyRequest) && $buyRequest->getStoreId()) {
            $storeId = $buyRequest->getStoreId();
        } else {
            $storeId = $this->storeManager->getStore()->getId();
        }

        if (!$product instanceof Product) {
            $productId = (int)$product;
            try {
                /** @var Product $product */
                $product = $this->productRepository->getById($productId, false, $storeId);
            } catch (NoSuchEntityException $e) {
                throw new LocalizedException(__('Cannot specify product.'));
            }
        }

        if ($this->configHelper->useDraftInWishlist($storeId)) {
            if (is_string($buyRequest)) {
                $buyRequest = new DataObject(unserialize($buyRequest));
            } elseif (is_array($buyRequest)) {
                $buyRequest = new DataObject($buyRequest);
            } elseif (!$buyRequest instanceof DataObject) {
                $buyRequest = new DataObject();
            }

            if (!$this->configHelper->filterForConfigurableProduct()) {
                if ($product->getTypeId() === ConfigurableType::TYPE_CODE) {
                    $childproduct = $this->productHelper->getChildProduct($product, $buyRequest->getSuperAttribute());
                    if (!empty($childproduct)){
                        $product = $childproduct;
                    }
                }
            }

            if (!empty($product)){
                $productId = $product->getId();
                $drafts = $this->sessionHelper->getDraftIdsByProductId($productId);
                if (!empty($drafts[$productId])) {
                    foreach ($drafts[$productId] as $draftKey => $draftValue) {
                        $printformerProductId = $draftKey;
                        if ($buyRequest->getData('_processing_params')) {
                            $draftId = $buyRequest
                                ->getData('_processing_params')
                                ->getData('current_config')
                                ->getData($this->productHelper::COLUMN_NAME_DRAFTID);
                            if ($draftId) {
                                $buyRequest->setData($this->productHelper::COLUMN_NAME_DRAFTID, $draftId);
                            }
                        } elseif (!empty($draftValue)) {
                            $draftIdsStored = $buyRequest->getData($this->productHelper::COLUMN_NAME_DRAFTID);
                            if (!empty($draftIdsStored)){
                                $draftIds = $draftIdsStored . ',' . $draftValue;
                            } else {
                                $draftIds = $draftValue;
                            }
                            $buyRequest->setData(
                                $this->productHelper::COLUMN_NAME_DRAFTID,
                                $draftIds
                            );
                            $this->sessionHelper->unsetSessionUniqueIdByDraftId($draftValue);
                            $this->sessionHelper->unsetDraftId($productId, $printformerProductId, $storeId);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Wishlist $subject
     * @param $result
     * @return mixed
     */
    public function afterAddNewItem(Wishlist $subject, $result)
    {
        if ($this->configHelper->useDraftInWishlist()) {
            if (!empty($result) && !is_string($result)) {
                $buyRequest = $result->getBuyRequest();
                $resultProductId = $result->getProductId();
                if (!empty($buyRequest) && !empty($resultProductId)) {
                    $value = $buyRequest->getData($this->productHelper::COLUMN_NAME_DRAFTID);
                    if (!empty($value)) {
                        $option = array(
                            'code' => $this->productHelper::COLUMN_NAME_DRAFTID,
                            'value' => $value,
                            'product_id' => $resultProductId
                        );
                        $result->addOption($option)->saveItemOptions();
                    }
                }
            }
        }

        try {
            $this->registry->register(
                Helper\Config::REGISTRY_KEY_WISHLIST_NEW_ITEM_ID,
                $result->getData('wishlist_item_id')
            );
        } catch (\Exception $e) {
        }

        return $result;
    }
}
