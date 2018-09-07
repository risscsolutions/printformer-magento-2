<?php

namespace Rissc\Printformer\Block\Adminhtml\Products\Grid\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Backend\Block\Context;
use Rissc\Printformer\Helper\Product as PrintformerProductHelper;

class MagentoProducts extends AbstractRenderer
{
    /**
     * @var PrintformerProductHelper
     */
    protected $printformerProductHelper;

    /**
     * MagentoProducts constructor.
     * @param Context $context
     * @param PrintformerProductHelper $printformerProductHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        PrintformerProductHelper $printformerProductHelper,
        array $data = []
    ) {
        $this->printformerProductHelper = $printformerProductHelper;
        parent::__construct($context, $data);
    }

    public function render(DataObject $row)
    {
        $catalogProductPrintformerProducts = [];
        if($row->getMasterId()) {
            $catalogProductPrintformerProducts = $this->printformerProductHelper->getCatalogProductPrintformerProductsByMasterId($row->getMasterId());
        }

        $assigned = [];
        foreach($catalogProductPrintformerProducts as $assignedProduct) {
            if(isset($assignedProduct['product_id'])) {
                $assigned[] = '<a href="' . $this->_urlBuilder->getUrl('catalog/product/edit', ['id' => $assignedProduct['product_id']]) . '">' . $assignedProduct['product_id'] . '</a>';
            }
        }

        if(count($assigned) == 0) {
            return __('Not assigned');
        }

        return implode(', ', $assigned);
    }
}