<?php

namespace Rissc\Printformer\Cron;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\App\Filesystem\DirectoryList as MagentoDirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;

class CleanDesignImages
{
    private LoggerInterface $logger;
    private DirectoryList $directoryList;
    private File $file;
    private TimezoneInterface $timezone;

    private const DIRECTORY = 'printformer';
    private const DIRECTORY_PREVIEW = 'preview';
    private const DIRECTORY_THUMBS = 'thumbs';
    private const WEEK_IN_SECONDS = 604800;
    private const DAY_IN_SECONDS = 86400;

    /**
     * @param LoggerInterface $logger
     * @param DirectoryList $directoryList
     * @param File $file
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        LoggerInterface $logger,
        DirectoryList $directoryList,
        File $file,
        TimezoneInterface $timezone
    )
    {
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->timezone = $timezone;
    }

    public function execute()
    {
        try {
            $subFolders = [self::DIRECTORY_PREVIEW, self::DIRECTORY_THUMBS];

            foreach ($subFolders as $subFolder) {
                $subFolderPath = $this->directoryList->getPath(MagentoDirectoryList::PUB) . DIRECTORY_SEPARATOR
                    . MagentoDirectoryList::MEDIA . DIRECTORY_SEPARATOR . self::DIRECTORY . DIRECTORY_SEPARATOR
                    . $subFolder;

                if (!is_dir($subFolderPath)) {
                    $this->logger->warning(
                        'Image cleanup not possible. Can not access directory on path: ' . $subFolderPath
                    );
                    return;
                }

                $subFolderFiles = $this->file->readDirectory($subFolderPath);
                foreach ($subFolderFiles as $subFolderFile) {
                    if (!file_exists($subFolderFile)) {
                        return;
                    }

                    $fileModificationTime = time() - filemtime($subFolderFile);
                    if ($fileModificationTime <= self::WEEK_IN_SECONDS) {
                        return;
                    }

                    try {
                        $this->file->deleteFile($subFolderFile);
                        $this->logger->info(
                            'Deleted Cached Image: '
                            . $subFolderFile . ' (after more than 7 days) ('
                            . number_format($fileModificationTime / self::DAY_IN_SECONDS, 1)
                            . ' days old) on: ' . $this->timezone->date()->format('Y-m-d H:i:s')
                        );
                    } catch (FileSystemException $e) {
                        $this->logger->error(
                            'Failed to delete Cached Image: '
                            . $subFolderFile . ' (after more than 7 days) ('
                            . number_format($fileModificationTime / self::DAY_IN_SECONDS, 1)
                            . ' days old) on: ' . $this->timezone->date()->format('Y-m-d H:i:s')
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical(
                'Unexpected problem occurred while executing the cache images cleanup cron job: '
                . $e->getMessage()
            );
        }
    }
}
