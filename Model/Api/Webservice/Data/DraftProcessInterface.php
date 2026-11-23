<?php
namespace Rissc\Printformer\Model\Api\Webservice\Data;

interface DraftProcessInterface
{
    /**
     * Process an uploaded draft (replaces frontend controller printformer/process/draft).
     *
     * @param string $draftId
     * @param string|null $filename Basename of the temporary upload file in media/tmp/upload/<draftId>/
     * @return string[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute($draftId, $filename = null);
}
