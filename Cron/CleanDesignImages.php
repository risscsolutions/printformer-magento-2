<?php

namespace Rissc\Printformer\Cron;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\App\Filesystem\DirectoryList as MagentoDirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;

class CleanDesignImages
{
    private LoggerInterface $logger;
    private DirectoryList $directoryList;
    private File $file;
    const DIRECTORY = 'printformer';
    const DIRECTORY_PREVIEW = 'preview';
    const DIRECTORY_THUMBS = 'thumbs';

    /**
     * @param LoggerInterface $logger
     * @param DirectoryList $directoryList
     * @param File $file
     */
    public function __construct(
        LoggerInterface $logger,
        DirectoryList $directoryList,
        File $file
    )
    {
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        $this->file = $file;
    }

    public function execute()
    {
        $subFolders = [$this::DIRECTORY_PREVIEW, $this::DIRECTORY_THUMBS];

        foreach ($subFolders as $subFolder){
            //get folder to scan
            $subFolderPath = $this->directoryList->getPath(MagentoDirectoryList::PUB) . DIRECTORY_SEPARATOR
                . MagentoDirectoryList::MEDIA . DIRECTORY_SEPARATOR . $this::DIRECTORY . DIRECTORY_SEPARATOR
                . $subFolder;

            //read just that single directory
            $subFolderFiles =  $this->file->readDirectory($subFolderPath);

            foreach ($subFolderFiles as $subFolderFile){
                if (file_exists($subFolderFile)){
                    if (time()-filemtime($subFolderFile) > 168 * 3600){
                        try {
                            $this->file->deleteFile($subFolderFile);
                            $this->logger->info(
                                'This design image is older then 7 days and will be deleted now: '
                                . $subFolderFile
                            );
                        } catch (FileSystemException $e) {
                        }
                    }
                }
            }
        }
    }
}