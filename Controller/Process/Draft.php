<?php
namespace Rissc\Printformer\Controller\Process;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Rissc\Printformer\Gateway\Admin\Draft as DraftGateway;
use Rissc\Printformer\Helper\Order;
use Magento\Framework\Filesystem;
use Rissc\Printformer\Helper\Config as PrintformerConfig;

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
    /**
     * @var PrintformerConfig
     */
    private $printformerConfig;

    /**
     * Draft constructor.
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param LoggerInterface $logger
     * @param Filesystem $filesystem
     * @param Order $order
     * @param PrintformerConfig $printformerConfig
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        LoggerInterface $logger,
        Filesystem $filesystem,
        Order $order,
        PrintformerConfig $printformerConfig
    )
    {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
        $this->filesystem = $filesystem;
        $this->order = $order;
        $this->printformerConfig = $printformerConfig;
    }

    public function execute()
    {
        $this->logger->debug('draft-process callback started');

        $draftHash = $this->getRequest()->getParam('draft_id');
        $requestParams = ['draft_id' => $draftHash];

        if ($this->printformerConfig->getProcessingType() == DraftGateway::DRAFT_PROCESSING_TYPE_ASYNC || $this->printformerConfig->isV2Enabled()) {
            $result = $this->jsonFactory->create();

            $directoryWriteInstance = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $filePath = $this->getRequest()->getParam('filepath');
            if (isset($filePath)){
                $absoluteMediaPath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
                $fullAbsoluteFilePath = $absoluteMediaPath.$filePath;
                if (file_exists($fullAbsoluteFilePath)){
                    $directoryWriteInstance->delete($filePath);
                    $folders = DirectoryList::TMP.DIRECTORY_SEPARATOR.$this->order::API_UPLOAD_INTENT.DIRECTORY_SEPARATOR;
                    $oldParentTmpDraftDir = $absoluteMediaPath.$folders.$draftHash;
                    if (file_exists($oldParentTmpDraftDir)){
                        $scan = scandir($oldParentTmpDraftDir);
                        $scan = array_diff($scan, array('.'));
                        $scan = array_diff($scan, array('..'));
                        if (empty($scan)){
                            $directoryWriteInstance->delete($folders.$draftHash);
                        }
                    }
                }
            }

            $responseInfo = ['success' => true];
            $resultResponse = array_merge($responseInfo, $requestParams);

            $draftToSync = [];
            array_push($draftToSync, $draftHash);
            $this->logger->debug('upload drafts to process found:'.implode(",", $draftToSync));

            if ($this->order->checkItemByDraftHash($draftHash)) {
                $this->order->setAsyncOrdered($draftToSync);
            }

            http_response_code(200);
            header('Content-Type: application/json');
        } else {
            $responseInfo = [
                'failed' => true,
                'printformer-config processing_type currently' => $this->printformerConfig->getProcessingType(),
                'printformer-config processing_type required' => DraftGateway::DRAFT_PROCESSING_TYPE_ASYNC,
                'printformerConfig isV2Enabled currently' => !$this->printformerConfig->isV2Enabled(),
                'printformerConfig isV2Enabled required' => true
            ];
            $resultResponse = array_merge($responseInfo, $requestParams);
            $this->logger->debug('draft-process callback is not configured for asynchron');
        }

        $this->logger->debug('draft-process callback finished');
        return $result->setData($resultResponse);
    }
}