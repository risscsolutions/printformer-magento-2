<?php
namespace Rissc\Printformer\Model\Api\Webservice\Service;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;
use Rissc\Printformer\Helper\Api;
use Rissc\Printformer\Helper\Order;
use Rissc\Printformer\Model\Api\Webservice\Data\DraftProcessInterface;

/**
 * Web API replacement for the frontend controller printformer/process/draft.
 * Authentication/authorization is enforced by Magento via the ACL resource
 * Rissc_Printformer::callback (see etc/webapi.xml), before execute() runs.
 */
class DraftProcess implements DraftProcessInterface
{
    private Filesystem $filesystem;
    private LoggerInterface $logger;
    private Order $order;
    private Api $apiHelper;

    public function __construct(
        Filesystem $filesystem,
        LoggerInterface $logger,
        Order $order,
        Api $apiHelper
    ) {
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->order = $order;
        $this->apiHelper = $apiHelper;
    }

    /**
     * @inheritDoc
     */
    public function execute($draftId, $filename = null)
    {
        $draftHash = (string)$draftId;
        if ($draftHash === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $draftHash)) {
            throw new LocalizedException(__('Invalid draft_id.'));
        }

        // The path is built on the server: tmp/<upload-intent>/<draft_id>/<basename>
        $folders = DirectoryList::TMP . DIRECTORY_SEPARATOR . $this->apiHelper::API_UPLOAD_INTENT
            . DIRECTORY_SEPARATOR;
        $filepath = null;
        if ($filename !== null && $filename !== '') {
            $basename = basename((string)$filename);
            if ($basename === '' || $basename === '.' || $basename === '..' || strpos($basename, "\0") !== false) {
                $this->logger->warning('draft-process callback rejected: invalid filename');
                throw new LocalizedException(__('Invalid filename.'));
            }
            $filepath = $folders . $draftHash . DIRECTORY_SEPARATOR . $basename;
        }

        $draftToSync = [];
        try {
            $this->logger->debug('draft-process callback started');

            $directoryWrite = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            if ($filepath !== null) {
                $absoluteMediaPath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
                if (file_exists($absoluteMediaPath . $filepath)) {
                    $directoryWrite->delete($filepath);
                    $oldParentTmpDraftDir = $absoluteMediaPath . $folders . $draftHash;
                    if (file_exists($oldParentTmpDraftDir)
                        && empty(array_diff(scandir($oldParentTmpDraftDir), ['.', '..']))
                    ) {
                        $directoryWrite->delete($folders . $draftHash);
                    }
                }
            }

            $draftToSync[] = $draftHash;
            $this->logger->debug('upload drafts to process found:' . implode(',', $draftToSync));

            if ($this->order->checkItemByDraftHash($draftHash)) {
                $this->apiHelper->setAsyncOrdered($draftToSync);
            }

            $this->logger->debug('draft-process callback finished');
        } finally {
            foreach ($draftToSync as $id) {
                $this->apiHelper->setProcessingStateOnOrderItemByDraftId(
                    $id,
                    $this->apiHelper::ProcessingStateAfterUploadCallback
                );
            }
        }

        return ['success' => true, 'draft_id' => $draftHash];
    }
}
