<?php

namespace Rissc\Printformer\Block\System\Config\Form\Field;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Module\Dir\Reader;

class ReleaseVersion extends Field
{
    public const MODULE_CODE = 'Rissc_Printformer';
    /**
     * @var Reader
     */
    private $moduleReader;

    /**
     * @var File
     */
    private $filesystem;

    /**
     * @var SerializerInterface
     */
    private $serializer;


    /**
     * @param   Context  $context
     * @param   Reader  $moduleReader
     * @param   File  $filesystem
     * @param   SerializerInterface  $serializer
     * @param   array  $data
     */
    public function __construct(
        Context $context,
        Reader $moduleReader,
        File $filesystem,
        SerializerInterface $serializer,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->moduleReader = $moduleReader;
        $this->filesystem = $filesystem;
        $this->serializer = $serializer;
    }


    /**
     * Show Printformer-Extension-Version in configuration
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {

        $html = '<div style="padding-top:7px">';
        $html .= '<div><span> ';
        $html .= $this->getModuleVersion(self::MODULE_CODE);
        $html .= '</span></div>';
        $html .= '</div>';

        return $html;
    }


    /**
     * Read info about extension from composer json file
     *
     * @param string $moduleCode
     *
     * @return mixed
     */
    public function getModuleInfo(string $moduleCode)
    {
        if (!isset($this->moduleDataStorage[$moduleCode])) {
            $this->moduleDataStorage[$moduleCode] = [];

            try {
                $dir = $this->moduleReader->getModuleDir('', $moduleCode);
                $file = $dir . '/composer.json';

                $string = $this->filesystem->fileGetContents($file);
                $this->moduleDataStorage[$moduleCode] = $this->serializer->unserialize($string);
            } catch (FileSystemException $e) {
                $this->moduleDataStorage[$moduleCode] = [];
            }
        }

        return $this->moduleDataStorage[$moduleCode];
    }

    /**
     * Get version from composer json file
     *
     * @param   string  $moduleCode
     *
     * @return mixed
     */
    protected function getModuleVersion(string $moduleCode)
    {
        $module = $this->getModuleInfo($moduleCode);
        if ( ! is_array($module)
            || ! isset($module['version'])
        ) {
            return __('Version not found');
        }

        return $module['version'];
    }

}
