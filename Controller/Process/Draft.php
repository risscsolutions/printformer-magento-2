<?php

namespace Rissc\Printformer\Controller\Process;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;
use Rissc\Printformer\Helper\Api;
use Rissc\Printformer\Helper\Order;

/**
 * To process specific draft
 *
 * Class Draft
 *
 * @package Rissc\Printformer\Controller\Process
 */
class Draft extends Action
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Order
     */
    private $order;
    private Api $apiHelper;

    /**
     * @param   Context          $context
     * @param   JsonFactory      $jsonFactory
     * @param   LoggerInterface  $logger
     * @param   Filesystem       $filesystem
     * @param   Order            $order
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        LoggerInterface $logger,
        Filesystem $filesystem,
        Order $order,
        Api $apiHelper
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->logger      = $logger;
        $this->filesystem  = $filesystem;
        $this->order       = $order;
        $this->apiHelper   = $apiHelper;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $this->logger->debug('draft-process callback started');
            $draftHash     = $this->getRequest()->getParam('draft_id');
            $requestParams = ['draft_id' => $draftHash];

            $directoryWriteInstance
                      = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $filePath = $this->getRequest()->getParam('filepath');
            if (isset($filePath)) {
                $absoluteMediaPath
                                      = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)
                    ->getAbsolutePath();
                $fullAbsoluteFilePath = $absoluteMediaPath.$filePath;
                if (file_exists($fullAbsoluteFilePath)) {
                    $directoryWriteInstance->delete($filePath);
                    $folders              = DirectoryList::TMP
                        .DIRECTORY_SEPARATOR.$this->apiHelper::API_UPLOAD_INTENT
                        .DIRECTORY_SEPARATOR;
                    $oldParentTmpDraftDir = $absoluteMediaPath.$folders
                        .$draftHash;
                    if (file_exists($oldParentTmpDraftDir)) {
                        $scan = scandir($oldParentTmpDraftDir);
                        $scan = array_diff($scan, array('.'));
                        $scan = array_diff($scan, array('..'));
                        if (empty($scan)) {
                            $directoryWriteInstance->delete($folders
                                .$draftHash);
                        }
                    }
                }
            }

            $responseInfo   = ['success' => true];
            $resultResponse = array_merge($responseInfo, $requestParams);

            $draftToSync = [];
            array_push($draftToSync, $draftHash);
            $this->logger->debug('upload drafts to process found:'.implode(",",
                    $draftToSync));

            if ($this->order->checkItemByDraftHash($draftHash)) {
                $this->apiHelper->setAsyncOrdered($draftToSync);
            }

            http_response_code(200);
            header('Content-Type: application/json');

            $this->logger->debug('draft-process callback finished');
            $result->setData($resultResponse);
        } finally {
            if (isset($draftToSync) && !empty($draftToSync)) {
                foreach ($draftToSync as $draftId) {
                    $this->apiHelper->setProcessingStateOnOrderItemByDraftId($draftId,
                        $this->apiHelper::ProcessingStateAfterUploadCallback);
                }
            }
        }

        return $result;
    }
}
