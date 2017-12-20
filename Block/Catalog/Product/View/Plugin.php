<?php

namespace Rissc\Printformer\Block\Catalog\Product\View;

use Magento\Framework\View\Element\Template;

class Plugin
{
    /**
     * @param Template $block
     * @param string $result
     *
     * @return string
     */
    public function afterToHtml($block, $result)
    {
        $request = $block->getRequest();
        $isConfigure = 'false';
        if($request->getModuleName() == 'checkout' && $request->getActionName() == 'configure') {
            $isConfigure = 'true';
        }

        $configureScript = '
            <script type="text/javascript">
                var isConfigure = ' . $isConfigure . ';
            </script>
        ';

        if($block->getNameInLayout() == 'product.info.addtocart' || $block->getNameInLayout() == 'product.info.addtocart.additional') {
            /** @var \Rissc\Printformer\Block\Catalog\Product\View\Printformer $_printformerBlock */
            $printformerBlock = $block->getLayout()->createBlock('Rissc\Printformer\Block\Catalog\Product\View\Printformer');
            $printformerBlock->setTemplate('Rissc_Printformer::catalog/product/view/printformer.phtml');

            $result = $configureScript . $printformerBlock->toHtml() . $result;
        }

        return $result;
    }
}