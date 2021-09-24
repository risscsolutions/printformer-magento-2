<?php

namespace Rissc\Printformer\Block\Adminhtml\Group\Markdown;

use Rissc\Printformer\Block\Adminhtml\Group\Markdown;

class Changelog extends Markdown
{
    /**
     * @return string
     */
    protected function getFilename(): string
    {
        return 'CHANGELOG';
    }
}
