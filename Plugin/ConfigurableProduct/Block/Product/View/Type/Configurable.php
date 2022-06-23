<?php

namespace Rissc\Printformer\Plugin\ConfigurableProduct\Block\Product\View\Type;

use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as Subject;
use \Magento\Framework\Json\EncoderInterface;
use \Magento\Framework\Json\DecoderInterface;
use Magento\Store\Model\StoreManager;
use Rissc\Printformer\Helper\Config;
use Rissc\Printformer\Helper\Media;
use Rissc\Printformer\Block\Catalog\Product\View\Printformer as PrintformerBlock;
use Rissc\Printformer\Helper\Product as PrintformerProductHelper;
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
     * @var Media
     */
    private Media $mediaHelper;

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
    private Config $configHelper;

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
        Media $mediaHelper,
        PrintformerBlock $printformerBlock,
        PrintformerProductHelper $printformerProductHelper,
        StoreManager $storeManager,
        ProductRepositoryInterface $productRepository,
        Config $configHelper
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
            foreach ($config['images'] as $productId => $image) {
                $imagesResult = [];
                $product = $this->productRepository->getById($productId);
                $images = $product->getMediaGalleryImages();

                $pfProducts = $this->printformerProductHelper->getPrintformerProductsForFrontendConfigurationLogic(
                    $productId,
                    $this->storeManager->getStore()->getId()
                );
                foreach ($pfProducts as $pfProduct) {
                    $draftId = $this->printformerProductHelper->getDraftId($pfProduct->getId(), $pfProduct->getProductId());
                    if (!empty($draftId)){
                        $imagesResult = $this->mediaHelper->loadDraftImagesFormattedToResultCollection($draftId, $images);
                    }
                }

                if (!empty($imagesResult)){
                    $images = $imagesResult->getItems();
                    if (!empty($images)) {
                        $config['images'][$productId] = [];
                    }

                    foreach ($images as $image) {
                        $config['images'][$productId][] = $image->getData();
                    }
                }
            }
            $result = $this->encoder->encode($config);
        }

        return $result;
    }
}