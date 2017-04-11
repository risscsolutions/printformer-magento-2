<?php

namespace Rissc\Printformer\Block\Adminhtml\History\Grid\Renderer;

use \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use \Magento\Framework\DataObject;
use \DOMDocument;

/**
 * Class Data
 * @package Rissc\Printformer\Block\Adminhtml\History\Grid\Renderer
 */
class Data
    extends AbstractRenderer
{
    /**
     * @param DataObject $row
     *
     * @return mixed|string
     */
    public function render(DataObject $row)
    {
        /** @var \Rissc\Printformer\Model\History\Log $row */

        /** @var \Magento\Backend\Block\Widget\Grid\Column $column */
        $column = $this->getColumn();
        $html = '';

        $html .= '<div style="width:400px;">';
        $html .= '<a id="trigger_' . $row->getId() . '_' . $column->getIndex() . '" class="hide-trigger" href="javascript:;">' . __('Show Content') . '</a>';
        $html .= '<div id="content_' . $row->getId() . '_' . $column->getIndex() . '" class="hidden hide-content">';
        $html .= '  <pre>' . htmlentities($this->_niceXml($row->getData($column->getIndex()))) . '</pre>';
        $html .= '</div>';

        $js = '<script type="text/javascript">';
        $js .= 'require([\'jquery\', \'jquery/ui\'], function($){ 
            $(window).load(function(){
                var trigger = \'#trigger_' . $row->getId() . '_' . $column->getIndex() . '\';
                var content = \'#content_' . $row->getId() . '_' . $column->getIndex() . '\';
                if($(trigger).length && $(content).length) {
                    $(trigger).click(function(){
                        if($(content).hasClass(\'hidden\')) {
                            $(trigger).text(\'' . __('Hide Content') . '\');
                            $(content).removeClass(\'hidden\');
                        } else {
                            $(trigger).text(\'' . __('Show Content') . '\');
                            $(content).addClass(\'hidden\');
                        }
                    });
                }
            });
        });';
        $js .= '</script>';

        $html .= $js;
        $html .= '</div>';

        return $html;
    }

    /**
     * @param string $data
     *
     * @return mixed
     */
    protected function _niceXml($data)
    {
        if(preg_match('/xml/i', $data))
        {
            if ($xml = simplexml_load_string($data))
            {
                $domxml = new DOMDocument('1.0');
                $domxml->preserveWhiteSpace = false;
                $domxml->formatOutput = true;
                $domxml->loadXML($xml->asXML());
                
                return $domxml->saveXML();
            }
        }

        return $data;
    }
}