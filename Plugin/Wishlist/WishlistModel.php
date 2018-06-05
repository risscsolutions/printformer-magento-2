<?php

namespace Rissc\Printformer\Plugin\Wishlist;

use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Wishlist\Model\Wishlist;
use Rissc\Printformer\Setup\InstallSchema;
use Rissc\Printformer\Helper as Helper;
use Rissc\Printformer\Helper\Session;

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
     * @var Session
     */
    protected $session;

    /**
     * WishlistModel constructor.
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param Session $session
     */
    public function __construct(
        Registry $registry,
        StoreManagerInterface $storeManager,
        Session $session
    ) {
        $this->registry = $registry;
        $this->storeManager = $storeManager;
        $this->session = $session;
    }

    /**
     * @param Wishlist $subject
     * @param $product
     * @param null $buyRequest
     * @param bool $forciblySetQty
     */
    public function beforeAddNewItem(
        Wishlist $subject,
        $product,
        $buyRequest = null,
        $forciblySetQty = false
    ) {
        if ($product instanceof \Magento\Catalog\Model\Product) {
            $productId = $product->getId();
        } else {
            $productId = (int)$product;
        }

        if (isset($buyRequest) && $buyRequest->getStoreId()) {
            $storeId = $buyRequest->getStoreId();
        } else {
            $storeId = $this->storeManager->getStore()->getId();
        }

        if (is_string($buyRequest)) {
            $buyRequest = new \Magento\Framework\DataObject(unserialize($buyRequest));
        } elseif (is_array($buyRequest)) {
            $buyRequest = new \Magento\Framework\DataObject($buyRequest);
        } elseif (!$buyRequest instanceof \Magento\Framework\DataObject) {
            $buyRequest = new \Magento\Framework\DataObject();
        }

        if ($buyRequest->getData('_processing_params')) {
            $existDraftId = $buyRequest
                ->getData('_processing_params')
                ->getData('current_config')
                ->getData(InstallSchema::COLUMN_NAME_DRAFTID);
            if ($existDraftId) {
                $buyRequest->setData(InstallSchema::COLUMN_NAME_DRAFTID, $existDraftId);
            }
        } elseif ($this->session->getDraftId($productId, $storeId)) {
            $buyRequest->setData(
                InstallSchema::COLUMN_NAME_DRAFTID,
                $this->session->getDraftId($productId, $storeId)
            );
            $this->session->unsDraftId($productId, $storeId);
        }
    }

    /**
     * @param Wishlist $subject
     * @param $result
     * @return mixed
     */
    public function afterAddNewItem(Wishlist $subject, $result)
    {
        if (!is_string($result)) {
            $value = $result->getBuyRequest()->getData(InstallSchema::COLUMN_NAME_DRAFTID);
            $option = array(
                'code' => InstallSchema::COLUMN_NAME_DRAFTID,
                'value' => $value,
                'product_id' => $result->getProductId()
            );
            $result->addOption($option)->saveItemOptions();
            $this->registry->register(
                Helper\Config::REGISTRY_KEY_WISHLIST_NEW_ITEM_ID,
                $result->getData('wishlist_item_id')
            );
        }
        return $result;
    }
}
