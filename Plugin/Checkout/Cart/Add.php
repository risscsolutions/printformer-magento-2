<?php

namespace Rissc\Printformer\Plugin\Checkout\Cart;

use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Checkout\Controller\Cart\Add as SubjectAdd;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class Add
{
    /**
     * @param SubjectAdd $subject
     * @param \Magento\Framework\Controller\Result\Redirect $result
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function afterExecute(SubjectAdd $subject, $result)
    {
        if (!$subject->getRequest()->isAjax()) {
            $rerirectUrl = $subject->getRequest()->getParam('redirect_url');
            if ($rerirectUrl) {
                $result->setUrl($rerirectUrl);
            }

            return $result;
        }

        $objm = ObjectManager::getInstance();
        /** @var ProductFactory $productFactory */
        $productFactory = $objm->get(ProductFactory::class);
        /** @var ProductResource $productResource */
        $productResource = $objm->get(ProductResource::class);
        $product = $productFactory->create();
        $productResource->load($product, (int)$subject->getRequest()->getParam('product'));

        $resultArray = [];
        if ($product && $product->getId()) {
            $resultArray['backUrl'] = $product->getProductUrl();
        }

        $subject->getResponse()->representJson(
            $objm->get(JsonHelper::class)->jsonEncode($resultArray)
        );
    }
}