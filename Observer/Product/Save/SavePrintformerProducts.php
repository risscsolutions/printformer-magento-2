<?php

namespace Rissc\Printformer\Observer\Product\Save;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SavePrintformerProducts implements ObserverInterface
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * SavePrintformerProducts constructor.
     * @param ManagerInterface $eventManager
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ManagerInterface $eventManager,
        ResourceConnection $resourceConnection
    ) {
        $this->eventManager = $eventManager;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getProduct();

        /** @var \Magento\Catalog\Controller\Adminhtml\Product\Save $controller */
        $controller = $observer->getController();

        /** @var \Magento\Framework\App\RequestInterface $request */
        $request = $controller->getRequest();

        /** @var array $params */
        $params = $request->getParams();

        $connection = $this->resourceConnection->getConnection();

        $data = [];

        if(isset($params['product']['printformer_products'])) {
            foreach ($params['product']['printformer_products'] as $record) {
                if(!isset($record['is_delete']) || $record['is_delete'] != 1) {
                    $item = [
                        'product_id' => $product->getId(),
                        'printformer_product_id' => $record['id'],
                    ];
                    $data[] = $item;
                }
            }
        }

        $this->eventManager->dispatch('catalog_product_printformer_product_insert_before', ['product' => $product, 'data' => $data]);

        $connection->beginTransaction();
        $connection->delete('catalog_product_printformer_product', ['product_id = ?' => $product->getId()]);
        if(count($data) > 0) {
            $connection->insertMultiple('catalog_product_printformer_product', $data);
        }
        $connection->commit();

        $this->eventManager->dispatch('catalog_product_printformer_product_insert_after', ['product' => $product, 'data' => $data]);
    }
}