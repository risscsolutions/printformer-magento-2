<?php

namespace Rissc\Printformer\Block\Adminhtml\Sales\Order\View\Items\Renderer;

use Magento\Framework\DataObject;
use Magento\Sales\Block\Adminhtml\Order\View\Items\Renderer\DefaultRenderer;
use Rissc\Printformer\Helper\Url;

class DefaultRendererPlugin extends DefaultRenderer
{
    /**
     * @var Url
     */
    protected $_urlHelper;

    /**
     * @param Url $urlHelper
     */
    public function __construct(
        Url $urlHelper
    ) {
        $this->_urlHelper = $urlHelper;
    }

    /**
     * @param DefaultRenderer $renderer
     * @param \Closure $proceed
     * @param DataObject $item
     * @param $column
     * @param null $field
     * @return string
     */
    public function aroundGetColumnHtml(
        DefaultRenderer $renderer,
        \Closure $proceed,
        DataObject $item,
        $column,
        $field = null
    ) {
        $html = $proceed($item, $column, $field);
        if ($column == 'product' && $item->getPrintformerDraftid()) {
            if ($renderer->canDisplayContainer()) {
                $html .= '<div id="printformer-draftid">';
            }

            $html .= '<div><br /><span>' . __('Draft ID') . ':&nbsp;</span>';
            $html .= '<span>';
            $html .= $renderer->escapeHtml($item->getPrintformerDraftid());
            $html .= '</span></div>';
            $html .= '<!-- Trigger the modal with a button -->
                        <button type="button" class="btn btn-info btn-lg" id="openModal" style="margin-top: 1em;">Open Editor</button>';
            $html .= '  <div id="popup-modal" style="width: 100%; height: 70vh;">                            
                        </div>
                        <script>
                            require(
                                [
                                    \'jquery\',
                                    \'Magento_Ui/js/modal/modal\'
                                ],
                                function(
                                    $,
                                    modal
                                ) {
                                    var options = {
                                        type: \'popup\',
                                        responsive: true,
                                        innerScroll: false,
                                        title: \'Draft Editor\',
                                        buttons: []
                                    };
                                    
                                    var popup = modal(options, $(\'#popup-modal\'));                                                    
                                    $("#openModal").on("click",function() {                                     
                                        var i = document.createElement("iframe");
                                        i.src = "'.$this->getEditorUrl($item).'";
                                        i.id = "iframe";
                                        i.scrolling = "auto";
                                        i.frameborder = "0";
                                        i.width = "100%";
                                        i.height = "100%";
                                        document.getElementById("popup-modal").appendChild(i);
                                        
                                        $(\'#popup-modal\').modal(\'openModal\');
                                    });
                                     
                                     
                                    //remove iframe on X click event
                                    $(\'.action-close\').click(function(){
                                        //remove iframe on close
                                        var iframe = document.getElementById("iframe");
                                        iframe.parentNode.removeChild(iframe);                                        
                                    });
                                    
                                    //remove iframe on esc klick event
                                    $(document).keyup(function(e) {
                                         if (e.keyCode == 27) { 
                                             //remove iframe on close
                                            var iframe = document.getElementById("iframe");
                                            iframe.parentNode.removeChild(iframe); 
                                        }
                                    });
                                                                       
                                }                            
                            );
                        </script>                                        
                        
                      
                      ';

            if ($item->getPrintformerOrdered()) {
                $html .= '<div style="margin-top: 5px;"><a class="action-default scalable action-save action-secondary" href="' . $this->getPdfUrl($item) . '" target="_blank">';
                $html .= __('Show print file');
                $html .= '</a></div>';
            }

            if ($renderer->canDisplayContainer()) {
                $html .= '</div>';
            }
        }

        return $html;
    }

    /**
     * @param DataObject $item
     * @return string
     */
    public function getPdfUrl(\Magento\Framework\DataObject $item)
    {
        return $this->_urlHelper->setStoreId($item->getPrintformerStoreid())
            ->getAdminPdfUrl($item->getPrintformerDraftid(), $item->getOrder()->getQuoteId());
    }

    /**
     * @param DataObject $item
     * @return string
     */
    public function getThumbImgUrl(\Magento\Framework\DataObject $item)
    {
        return $this->_urlHelper->setStoreId($item->getPrintformerStoreid())
            ->getThumbImgUrl($item->getPrintformerDraftid());
    }

    /**
     * @param DataObject $item
     * @return string
     */
    public function getEditorUrl(\Magento\Framework\DataObject $item)
    {
        return $this->_urlHelper->setStoreId($item->getPrintformerStoreid())
            ->getAdminEditorUrl($item->getPrintformerDraftid());
    }
}
