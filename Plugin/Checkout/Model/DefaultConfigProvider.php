<?php

namespace Rissc\Printformer\Plugin\Checkout\Model;

use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Model\Quote\Item;
use Rissc\Printformer\Helper\Config;
use Rissc\Printformer\Helper\Media;

class DefaultConfigProvider
{
    /**
     * @var CheckoutSession $checkoutSession
     */
    private CheckoutSession $checkoutSession;

    /**
     * @var CartItemRepositoryInterface $itemRepository
     */
    private CartItemRepositoryInterface $itemRepository;

    /**
     * @var Image|mixed $imageHelper
     */
    private Image $imageHelper;

    /**
     * @var ItemResolverInterface|mixed $itemResolver
     */
    private ItemResolverInterface $itemResolver;

    /**
     * @var Config $configHelper
     */
    private Config $configHelper;

    /**
     * @var Media $mediaHelper
     */
    private Media $mediaHelper;

    /**
     * @param   CheckoutSession              $checkoutSession
     * @param   CartItemRepositoryInterface  $itemRepository
     * @param   Image                        $imageHelper
     * @param   ItemResolverInterface        $itemResolver
     * @param   Config                       $configHelper
     * @param   Media                        $mediaHelper
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CartItemRepositoryInterface $itemRepository,
        Image $imageHelper,
        ItemResolverInterface $itemResolver,
        Config $configHelper,
        Media $mediaHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->itemRepository  = $itemRepository;
        $this->configHelper    = $configHelper;
        $this->mediaHelper     = $mediaHelper;
        $this->imageHelper     = $imageHelper
            ?: ObjectManager::getInstance()->get(Image::class);
        $this->itemResolver    = $itemResolver
            ?: ObjectManager::getInstance()->get(ItemResolverInterface::class);
    }


    /**
     * @param $subject
     * @param $result
     *
     * @return mixed
     */
    public function afterGetConfig(
        $subject,
        $result
    ) {
        if ($this->configHelper->isUseImagePreview()) {
            try {
                $quote = $this->checkoutSession->getQuote();
            } catch (NoSuchEntityException|LocalizedException $e) {
            }
            if (isset($quote)) {
                $quoteId             = $quote->getId();
                $result['imageData'] = $this->getImages($quoteId);
            }
        }

        return $result;
    }

    private function getImages($quoteId)
    {
        $itemData = [];

        /** @see code/Magento/Catalog/Helper/Product.php */
        $items = $this->itemRepository->getList($quoteId);
        /** @var Item $cartItem */
        foreach ($items as $cartItem) {
            $itemData[$cartItem->getItemId()]
                = $this->getProductImageData($cartItem);
        }

        return $itemData;
    }

    /**
     * Get product image data
     *
     * @param   Item  $cartItem
     *
     * @return array
     */
    public function getProductImageData(Item $cartItem): array
    {
        $imageHelper = $this->imageHelper->init(
            $this->itemResolver->getFinalProduct($cartItem),
            'mini_cart_product_thumbnail'
        );

        $draftIds
            = $this->configHelper->getDraftIdsFromSpecificItemType($cartItem);
        if (!empty($draftIds)) {
            $imageUrl
                = $this->mediaHelper->loadThumbsImageUrlByDraftId($draftIds);
        }

        if (!isset($imageUrl)) {
            $imageUrl = $imageHelper->getUrl();
        }


        $imageData = [
            'src'    => $imageUrl,
            'alt'    => $imageHelper->getLabel(),
            'width'  => $imageHelper->getWidth(),
            'height' => $imageHelper->getHeight(),
        ];

        return $imageData;
    }
}
