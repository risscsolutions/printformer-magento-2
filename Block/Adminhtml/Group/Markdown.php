<?php

namespace Rissc\Printformer\Block\Adminhtml\Group;

use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\Js;
use Rissc\Printformer\Helper\Files;

class Markdown extends Fieldset
{
    /**
     * @var $filename
     */
    protected $filename;

    /**
     * @var Files
     */
    private $files;

    /**
     * @param   Context  $context
     * @param   Session  $authSession
     * @param   Js       $jsHelper
     * @param   Files    $files
     * @param   array    $data
     */
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        Files $files,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->files = $files;
    }

    /**
     * @param   AbstractElement  $element
     *
     * @return string
     */
    protected function _getHeaderCommentHtml($element)
    {
        $resultHtml = $this->getImageHtml();
        $resultHtml .= $this->files->getHtmlFromMarkdownFile($this->getFilename());

        return $element->getComment() ? '<div class="comment">'.$resultHtml
            .$element->getComment().'</div>' : '';
    }

    /**
     * @return string
     */
    protected function getImageHtml(): string
    {
        $imageFilePath = $this->files->getRisscLogoUrl();
        $resultHtml    = '';
        if (!empty($imageFilePath)) {
            $resultHtml
                = '<p style="margin-bottom: 20px"><a href="https://www.rissc.de/"><img style="width: 70px;" src="'
                .$imageFilePath.'" alt="image"></a></p>';
        }

        return $resultHtml;
    }

    /**
     * @return string
     */
    protected function getFilename(): string
    {
        return $this->filename;
    }
}
