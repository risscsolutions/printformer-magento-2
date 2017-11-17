<?php

namespace Rissc\Printformer\Observer\Product\View\Buttons;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;

use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product;
use Rissc\Printformer\Gateway\User\Draft;
use Rissc\Printformer\Model\ProductFactory as PfProductFactory;
use Rissc\Printformer\Model\Product as PfProduct;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Framework\App\Request\Http;

class Observer implements ObserverInterface
{
    /** @var ProductFactory */
    protected $_productFactory;

    /** @var PfProductFactory */
    protected $_pfProductFactory;

    /** @var Draft */
    protected $_draftGateway;

    /** @var \Magento\Framework\App\Request\Http */
    private $_request;

    public function __construct(
        Http $request,
        ProductFactory $productFactory,
        PfProductFactory $pfProductFactory,
        StoreRepositoryInterface $storeRepository,
        Draft $draft
    )
    {
        $this->_productFactory = $productFactory;
        $this->_pfProductFactory = $pfProductFactory;
        $this->_draftGateway = $draft;
        $this->_request = $request;
    }

    public function execute(EventObserver $observer)
    {
        /** @var Product $product */
        $product = $this->_productFactory->create();
        $connection = $product->getResource()->getConnection();
        $table = $connection->getTableName('catalog_product_entity_text');

        $product = $observer->getProduct();

        $product->getResource()->load($product, $product->getId());

        $storeId = (int) $this->_request->getParam('store', 0);

        /** @var PfProduct $pfProduct */
        $pfProduct = $this->getPfProductByMasterId($product->getPrintformerProduct(), $storeId);
        if($pfProduct && $pfProduct->getId())
        {
            $intents = explode(',', $pfProduct->getIntents());
            $attribute = $product->getResource()->getAttribute('printformer_capabilities');
            if ($attribute->getId())
            {
                $currentlySelectedValueFromMultiselect = $attribute->getFrontend()->getValue($product);
                $multiselectAsArray = explode(',', $currentlySelectedValueFromMultiselect);
                $allOptionIds = [];
                foreach ($multiselectAsArray as $singleMultiselectItem) {
                    $optionIdOfSingleItem = $attribute->getSource()->getOptionId(ltrim($singleMultiselectItem));
                    $allOptionIds[] = $optionIdOfSingleItem;
                }

                $avaliableIntents = [];
                $options = $attribute->getOptions();
                foreach ($options as $option)
                {
                    if(in_array($this->_draftGateway->getIntent($option->getLabel()), $intents))
                    {
                        $avaliableIntents[] = (int) $option->getValue();
                    }
                }

                $qry = '
                    UPDATE `' . $table . '`
                        SET
                            `value` = \'' . implode(',', $allOptionIds) . '\'
                        WHERE
                            `attribute_id` = ' . $attribute->getId() . '
                        AND
                            `store_id` = ' . $storeId . '
                        AND
                            `entity_id` = ' . $product->getId() . ';';

                $connection->query($qry);
            }
        }

    }

    protected function getPfProductByMasterId($masterId, $storeId)
    {
        /** @var PfProduct $pfProduct */
        $pfProduct = $this->_pfProductFactory->create();
        $pfProductCollection = $pfProduct->getCollection()
            ->addFieldToFilter('master_id', ['eq' => $masterId])
            ->addFieldToFilter('store_id', ['eq' => $storeId]);

        if($pfProductCollection->count() > 0)
        {
            return $pfProductCollection->getFirstItem();
        }

        return null;
    }
}