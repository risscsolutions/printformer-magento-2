<?php
namespace Rissc\Printformer\Block\Catalog\Product\View;

use \Magento\Framework\View\Element\Template as TemplateBlock;

class Plugin
{
    /**
     * @param TemplateBlock $block
     * @param string $result
     *
     * @return mixed
     */
    public function afterToHtml($block, $result)
    {
        $_request = $block->getRequest();
        $isConfigure = 'false';
        if(
            $_request->getModuleName() == 'checkout' &&
            $_request->getActionName() == 'configure'
        )
        {
            $isConfigure = 'true';
        }

        $configureScript = '
            <script type="text/javascript">
                var isConfigure = ' . $isConfigure . ';
            </script>
        ';

        if(
            $block->getNameInLayout() == 'product.info.addtocart' ||
            $block->getNameInLayout() == 'product.info.addtocart.additional'
        )
        {
            /** @var \Rissc\Printformer\Block\Catalog\Product\View\Printformer $_printformerBlock */
            $_printformerBlock = $block->getLayout()->createBlock('Rissc\Printformer\Block\Catalog\Product\View\Printformer');
            $_printformerBlock->setTemplate('Rissc_Printformer::catalog/product/view/printformer.phtml');

            $result = $configureScript . $_printformerBlock->toHtml() . $result;
        }

        return $result;
    }
}