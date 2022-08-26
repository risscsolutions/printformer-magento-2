<?php
namespace Rissc\Printformer\Controller\Delete;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\App\ObjectManager;
use Rissc\Printformer\Helper\Session;

class Draft
    extends Action
{
    /** @var ProductFactory */
    protected $_productFactory;

    /** @var ProductResource */
    protected $_productResource;

    public function __construct(
        Context $context,
        ProductFactory $productFactory,
        ProductResource $productResource
    ) {
        $this->_productFactory = $productFactory;
        $this->_productResource = $productResource;

        parent::__construct($context);
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();

        /** @var Product $product */
        $product = $this->_productFactory->create();
        $this->_productResource->load($product, intval($params['product_id']));

        $productId = $params['selected_product_id'];
        $draftId = $params['selected_product_draft_id'];
        $printformerProductId = $params['printformer_product'];

        if (isset($productId) && isset($draftId) && isset($printformerProductId)) {
            $connection = $this->_productResource->getConnection();

            $objm = ObjectManager::getInstance();
            /** @var Session $sessionHelper */
            $sessionHelper = $objm->get(Session::class);

            if (!empty($draftId)) {
                $connection->query("
                    DELETE FROM " . $connection->getTableName('printformer_draft') . "
                    WHERE `draft_id` = '" . $draftId . "';
                ");

                $sessionHelper->unsetCurrentIntent();

                $sessionUniqueIds = $sessionHelper->getCustomerSession()->getSessionUniqueIds();
                if (isset($sessionUniqueIds[$productId][$printformerProductId])) {
                    unset($sessionUniqueIds[$productId][$printformerProductId]);
                    $sessionHelper->getCustomerSession()->setData('session_unique_ids', $sessionUniqueIds);
                }
                $this->messageManager->addSuccessMessage(__('Draft has been successfully deleted.'));
            }
        }

        header('Location: ' . $product->getProductUrl());
        exit();
    }
}