<?php

namespace Rissc\Printformer\Plugin\ConfigurableProduct\Block\Product\View\Type;

use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as Subject;
use Magento\Framework\App\RequestInterface;
use \Magento\Framework\Json\EncoderInterface;
use \Magento\Framework\Json\DecoderInterface;
use Magento\Store\Model\StoreManager;
use Rissc\Printformer\Block\Catalog\Product\View\Printformer as PrintformerBlock;
use Rissc\Printformer\Helper\Product as PrintformerProductHelper;
use Rissc\Printformer\Helper\Config as ConfigHelper;
use Rissc\Printformer\Helper\Media as MediaHelper;
use Rissc\Printformer\Helper\Cart as CartHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Configurable
{
    /**
     * @var EncoderInterface $encoder
     */
    private EncoderInterface $encoder;

    /**
     * @var DecoderInterface $decoder
     */
    private DecoderInterface $decoder;

    /**
     * @var MediaHelper
     */
    private MediaHelper $mediaHelper;

    /**
     * @var PrintformerBlock
     */
    private PrintformerBlock $printformerBlock;

    /**
     * @var PrintformerProductHelper
     */
    private PrintformerProductHelper $printformerProductHelper;
    private StoreManager $storeManager;
    private ProductRepositoryInterface $productRepository;
    private ConfigHelper $configHelper;
    private RequestInterface $request;
    private CartHelper $cartHelper;

    /**
     * @param EncoderInterface $encoder
     * @param DecoderInterface $decoder
     * @param Media $mediaHelper
     * @param PrintformerBlock $printformerBlock
     * @param PrintformerProductHelper $printformerProductHelper
     * @param StoreManager $storeManager
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        EncoderInterface $encoder,
        DecoderInterface $decoder,
        MediaHelper $mediaHelper,
        PrintformerBlock $printformerBlock,
        PrintformerProductHelper $printformerProductHelper,
        StoreManager $storeManager,
        ProductRepositoryInterface $productRepository,
        ConfigHelper $configHelper,
        RequestInterface $request,
        CartHelper $cartHelper
    )
    {
        $this->encoder = $encoder;
        $this->decoder = $decoder;
        $this->mediaHelper = $mediaHelper;
        $this->printformerBlock = $printformerBlock;
        $this->printformerProductHelper = $printformerProductHelper;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->configHelper = $configHelper;
        $this->request = $request;
        $this->cartHelper = $cartHelper;
    }

    /**
     * Load draft-images to result-collection for json-config
     *
     * @param Subject $subject
     * @param $result
     * @return mixed
     */
    public function afterGetJsonConfig(Subject $subject, $result)
    {
        if ($this->configHelper->isUseImagePreview()) {
            $config = $this->decoder->decode($result);
            $config['filterConfigurableProduct'] = $this->configHelper->filterForConfigurableProduct();
            foreach ($config['images'] as $productId => $image) {
                $product = $this->productRepository->getById($productId);
                $images = $product->getMediaGalleryImages();

                $pfProducts = $this->printformerProductHelper->getPrintformerProductsForFrontendConfigurationLogic(
                    $productId,
                    $this->storeManager->getStore()->getId()
                );

                $draftIds = [];
                if ($this->request->getModuleName() == 'wishlist') {
                    try {
                        $id = (int)$this->request->getParam('id');
                        $wishlistItem = $this->cartHelper->getWishlistItemModel()->loadWithOptions($id);
                        if ($wishlistItem) {
                            $buyRequest = $wishlistItem->getBuyRequest();
                            $draftIds = $buyRequest->getData($this->printformerProductHelper::COLUMN_NAME_DRAFTID);
                            if (!empty($draftIds)) {
                                $draftIds = explode(',', $draftIds);
                            }
                        }
                    } catch (\Exception $e) {
                    }
                } else {
                    foreach ($pfProducts as $pfProduct) {
                        $draftId = $this->printformerProductHelper->getDraftId($pfProduct->getId(), $pfProduct->getProductId());
                        if (!empty($draftId)) {
                            if (!$this->cartHelper->draftIsAlreadyUsedInCart($draftId) || $this->request->getModuleName() == 'checkout') {
                                if (!empty($draftId)) {
                                    array_push($draftIds, $draftId);
                                }
                            }
                        }
                    }
                }

                if (!empty($draftIds)){
                    $draftItem = $this->mediaHelper->loadDraftImagesToAdditionalImages($draftIds, $images);
                    if (!empty($draftItem)){
                        $images = $draftItem->getItems();

                        if (!empty($images)) {
                            $config['images'][$productId] = [];
                        }

                        foreach ($images as $image) {
                            $config['images'][$productId][] = $image->getData();
                        }
                    }
                }
            }
            $result = $this->encoder->encode($config);
        }

        return $result;
    }
}