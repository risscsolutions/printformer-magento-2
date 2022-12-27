<?php

namespace Rissc\Printformer\Block\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\ModuleListInterface;

class ReleaseVersion extends Field
{
    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @param   Context              $context
     * @param   ModuleListInterface  $moduleList
     * @param   array                $data
     */
    public function __construct(
        Context $context,
        ModuleListInterface $moduleList,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->moduleList = $moduleList;
    }


    /**
     * Show Printformer-Extension-Version in configuration
     *
     * @param   AbstractElement  $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $html = '<div style="padding-top:7px">';
        $html .= '<div><span> ';
        $html .= $this->getVersion();
        $html .= '</span></div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        $result = '?';
        $module = $this->moduleList->getOne($this->getModuleName());
        if (isset($module['setup_version'])) {
            $result = $module['setup_version'];
        }

        return $result;
    }
}
