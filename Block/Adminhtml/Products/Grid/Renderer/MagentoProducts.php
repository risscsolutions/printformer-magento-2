<?php

namespace Rissc\Printformer\Block\Adminhtml\Products\Grid\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Backend\Block\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Rissc\Printformer\Helper\Product as PrintformerProductHelper;

class MagentoProducts extends AbstractRenderer
{
    /**
     * @var PrintformerProductHelper
     */
    protected $printformerProductHelper;

    /**
     * @var ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * MagentoProducts constructor.
     * @param Context $context
     * @param PrintformerProductHelper $printformerProductHelper
     * @param ProductRepositoryInterface $productRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        PrintformerProductHelper $printformerProductHelper,
        ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        $this->printformerProductHelper = $printformerProductHelper;
        $this->_productRepository = $productRepository;
        parent::__construct($context, $data);
    }

    public function render(DataObject $row)
    {
        $catalogProductPrintformerProducts = [];
        if ($row->getId()) {
            $catalogProductPrintformerProducts = $this->printformerProductHelper->getCatalogProductPrintformerProductsByPrintformerProductId($row->getId(),$row->getStoreId());
        }

        $assigned = [];
        foreach ($catalogProductPrintformerProducts as $assignedProduct) {
            if (isset($assignedProduct['product_id'])) {
                $product = $this->_productRepository->getById($assignedProduct['product_id']);
                $assigned[] = '<a href="' . $this->_urlBuilder->getUrl('catalog/product/edit',
                        ['id' => $assignedProduct['product_id']]) . '">' . $product->getName() . '</a>';
            }
        }

        if (count($assigned) == 0) {
            return __('Not assigned');
        }

        return implode(', ', $assigned);
    }
}
