<?php
namespace Rissc\Printformer\Controller\Process;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Rissc\Printformer\Helper\Api;
use Magento\Framework\Filesystem;

/**
 * To process specific draft
 *
 * Class Draft
 * @package Rissc\Printformer\Controller\Process
 */
class Draft extends Action
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var Api
     */
    private $api;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Draft constructor.
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Api $api
     * @param LoggerInterface $logger
     * @param Filesystem $filesystem
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Api $api,
        LoggerInterface $logger,
        Filesystem $filesystem
    )
    {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->api = $api;
        $this->logger = $logger;
        $this->filesystem = $filesystem;
    }

    public function execute()
    {
        $this->logger->debug('draft-process callback started');
        $result = $this->jsonFactory->create();

        $draftId = $this->getRequest()->getParam('draft_id');
        $requestParams = ['draft_id' => $draftId];

        $directoryWriteInstance = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $filePath = $this->getRequest()->getParam('filepath');
        if (isset($filePath)){
            $absoluteMediaPath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
            $fullAbsoluteFilePath = $absoluteMediaPath.$filePath;
            if (file_exists($fullAbsoluteFilePath)){
                $directoryWriteInstance->delete($filePath);
                $folders = DirectoryList::TMP.DIRECTORY_SEPARATOR.$this->api::API_UPLOAD_INTENT.DIRECTORY_SEPARATOR;
                $oldParentTmpDraftDir = $absoluteMediaPath.$folders.$draftId;
                if (file_exists($oldParentTmpDraftDir)){
                    $scan = scandir($oldParentTmpDraftDir);
                    $scan = array_diff($scan, array('.'));
                    $scan = array_diff($scan, array('..'));
                    if (empty($scan)){
                        $directoryWriteInstance->delete($folders.$draftId);
                    }
                }
            }
        }

        $responseInfo = ['success' => true];
        $resultResponse = array_merge($responseInfo, $requestParams);
        $this->api->setAsyncOrdered($responseInfo);

        http_response_code(200);
        header('Content-Type: application/json');

        $this->logger->debug('draft-process callback finished');
        return $result->setData($resultResponse);
    }
}