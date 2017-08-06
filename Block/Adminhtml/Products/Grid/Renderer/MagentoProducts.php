<?php

namespace Rissc\Printformer\Block\Adminhtml\Products\Grid\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Catalog\Model\ProductFactory;
use Magento\Backend\Block\Context;

class MagentoProducts extends AbstractRenderer
{
    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * MagentoProducts constructor.
     * @param Context $context
     * @param ProductFactory $productFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        ProductFactory $productFactory,
        array $data = []
    ) {
        $this->_productFactory = $productFactory;
        parent::__construct($context, $data);
    }

    public function render(DataObject $row)
    {
        $product = $this->_productFactory->create();
        $productCollection = $product->getCollection()
            ->addAttributeToFilter(
                [
                    ['attribute' => 'printformer_product', 'eq' => $row->getMasterId()]
                ]
            )
            ->addStoreFilter($row->getStoreId())
            ->load();

        $assigned = [];
        foreach($productCollection as $assignedProduct) {
            $assigned[] = '<a href="' . $this->_urlBuilder->getUrl('catalog/product/edit', ['id' => $assignedProduct->getId()]) . '">' . $assignedProduct->getId() . '</a>';
        }

        if(count($assigned) == 0) {
            return __('Not assigned');
        }

        return implode(', ', $assigned);
    }
}