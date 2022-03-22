<?php

namespace Rissc\Printformer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Module\Dir;
use Magento\Framework\View\Asset\Repository;
use Parsedown as MarkdownParser;

class Files extends AbstractHelper
{
    /**
     * @var Dir $dir
     */
    private $dir;

    /**
     * @var MarkdownParser
     */
    private $markdownParser;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @param Context $context
     * @param Dir $dir
     * @param MarkdownParser $markdownParser
     */
    public function __construct(
        Context $context,
        Dir $dir,
        MarkdownParser $markdownParser,
        Repository $repository
    )
    {
        parent::__construct($context);
        $this->dir = $dir;
        $this->markdownParser = $markdownParser;
        $this->repository = $repository;
    }

    /**
     * @return string
     */
    public function getRisscLogoUrl(): string
    {
        $resultImageUrl = '';
        try {
            $resultImageUrl = $this->repository->getUrl($this->_getModuleName().'::images/rissc_logo_2020.png');
        } catch (\Exception $e) {
        }

        return $resultImageUrl;
    }

    /**
     * @param string $fileName
     * @return string
     */
    public function getHtmlFromMarkdownFile(string $fileName): string
    {
        $resultHtml = '';
        try {
            $moduleName = $this->_getModuleName();
            $modulePath = $this->dir->getDir($moduleName);
            $filePath = $modulePath . '/' . $fileName . '.md';
            $markdownFileContent = file_get_contents($filePath);
            $resultHtml = $this->markdownParser->text($markdownFileContent);
        } catch (\Exception $e) {
        }

        return $resultHtml;
    }
}