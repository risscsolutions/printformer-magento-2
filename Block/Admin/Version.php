<?php

namespace Rissc\Printformer\Block\Admin;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Module\ModuleListInterface;

/**
 * Class Version
 * @package Rissc\Printformer\Block\Admin
 */
class Version extends Template
{
    /**
     * @var ModuleListInterface
     */
    protected $moduleList;

    /**
     * Version constructor.
     * @param ModuleListInterface $moduleList
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        ModuleListInterface $moduleList,
        Template\Context $context,
        array $data = []
    ) {
        $this->moduleList = $moduleList;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        $module = $this->moduleList->getOne('Rissc_Printformer');
        if(isset($module['setup_version'])) {
            return $module['setup_version'];
        }
        return '?';
    }
}