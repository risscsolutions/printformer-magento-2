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
    const DIRECTORY = 'printformer';
    const DIRECTORY_PREVIEW = 'preview';
    const DIRECTORY_THUMBS = 'thumbs';
    const WEEK_IN_SECONDS = 604800;
    const DAY_IN_SECONDS = 86400;
    private TimezoneInterface $timezone;

    /**
     * @param LoggerInterface $logger
     * @param DirectoryList $directoryList
     * @param File $file
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
        $subFolders = [$this::DIRECTORY_PREVIEW, $this::DIRECTORY_THUMBS];

        foreach ($subFolders as $subFolder){
            //get folder to scan
            $subFolderPath = $this->directoryList->getPath(MagentoDirectoryList::PUB) . DIRECTORY_SEPARATOR
                . MagentoDirectoryList::MEDIA . DIRECTORY_SEPARATOR . $this::DIRECTORY . DIRECTORY_SEPARATOR
                . $subFolder;

            if (is_dir($subFolderPath)) {
                //read just that single directory
                $subFolderFiles =  $this->file->readDirectory($subFolderPath);

                foreach ($subFolderFiles as $subFolderFile){
                    if (file_exists($subFolderFile)){
                        $fileModificationTime = time()-filemtime($subFolderFile);
                        if ($fileModificationTime > $this::WEEK_IN_SECONDS){
                            try {
                                $this->file->deleteFile($subFolderFile);
                                $this->logger->info(
                                    'Deleted Cached Image: '
                                    . $subFolderFile.  ' (after more then 7 days) (' . number_format($fileModificationTime/$this::DAY_IN_SECONDS, 1) .' days old) on: '. $this->timezone->date()->format('Y-m-d H:i:s')
                                );
                            } catch (FileSystemException $e) {
                            }
                        }
                    }
                }
            } else {
                $this->logger->warning('Image cleanup not possible. Can not access directory on path: '.$subFolderPath);
            }
        }
    }
}