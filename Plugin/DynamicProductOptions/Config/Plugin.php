<?php
namespace Rissc\Printformer\Plugin\DynamicProductOptions\Config;

use \Itoris\DynamicProductOptions\Block\Options\Config as ConfigBlock;

/**
 * Class Plugin
 * @package Rissc\Printformer\Plugin\DynamicProductOptions\Config
 */
class Plugin
{
    /**
     * @param ConfigBlock $block
     * @param string      $result
     *
     * @return string
     */
    public function afterToHtml(ConfigBlock $block, $result)
    {
        $result .= "
            <script type=\"text/javascript\">
                require(['jquery'], function($){
                    $(window).load(function(e){
                        window.setTimeout(function(){
                            $(document).trigger('itorisDynamicoptionsLoadedAfter');
                        }, 500);
                    });
                });
            </script>
        ";

        return $result;
    }
}