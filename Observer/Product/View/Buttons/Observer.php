<?php

namespace Rissc\Printformer\Observer\Product\View\Buttons;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Catalog\Model\Product;
use Rissc\Printformer\Gateway\User\Draft;
use Rissc\Printformer\Model\ProductFactory as PfProductFactory;
use Rissc\Printformer\Model\Product as PfProduct;

class Observer implements ObserverInterface
{
    /**
     * @var PfProductFactory
     */
    protected $_pfProductFactory;

    /**
     * @var Draft
     */
    protected $_draftGateway;

    /**
     * @var RequestInterface
     */
    private $_request;

    /**
     * Observer constructor.
     * @param RequestInterface $request
     * @param PfProductFactory $pfProductFactory
     * @param Draft $draft
     */
    public function __construct(
        RequestInterface $request,
        PfProductFactory $pfProductFactory,
        Draft $draft
    ) {
        $this->_pfProductFactory = $pfProductFactory;
        $this->_draftGateway = $draft;
        $this->_request = $request;
    }

    public function execute(EventObserver $observer)
    {
        /** @var Product $product */
        $product = $observer->getProduct();

        $storeId = (int) $this->_request->getParam('store', 0);

        $pfProduct = $this->getPfProductByMasterId($product->getPrintformerProduct(), $storeId);
        if($pfProduct->getId()) {
            $attribute = $product->getResource()->getAttribute('printformer_capabilities');

            if ($attribute->getId()) {
                $currentlySelectedValueFromMultiselect = $attribute->getFrontend()->getValue($product);
                $multiselectAsArray = explode(',', $currentlySelectedValueFromMultiselect ?? '');
                $allOptionIds = [];
                foreach ($multiselectAsArray as $singleMultiselectItem) {
                    $allOptionIds[] = $attribute->getSource()->getOptionId(ltrim($singleMultiselectItem));
                }

                $connection = $product->getResource()->getConnection();
                $table = $connection->getTableName('catalog_product_entity_text');

                $qry = "
                    UPDATE `$table`
                        SET
                            `value` = ?
                        WHERE
                            `attribute_id` = ?
                        AND
                            `store_id` = ?
                        AND
                            `entity_id` = ?";

                $connection->query($qry, [implode(',', $allOptionIds), $attribute->getId(), $storeId, $product->getId()]);
            }
        }
    }

    /**
     * @param string $masterId
     * @param int $storeId
     * @return \Magento\Framework\DataObject|PfProduct
     */
    protected function getPfProductByMasterId($masterId, $storeId)
    {
        /** @var PfProduct $pfProduct */
        $pfProduct = $this->_pfProductFactory->create();
        $pfProductCollection = $pfProduct->getCollection()
            ->addFieldToFilter('master_id', ['eq' => $masterId])
            ->addFieldToFilter('store_id', ['eq' => $storeId]);

        if($pfProductCollection->count() > 0) {
            $pfProduct = $pfProductCollection->getFirstItem();
        }

        return $pfProduct;
    }
}