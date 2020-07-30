<?php
namespace Rissc\Printformer\Controller\Process;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Rissc\Printformer\Helper\Api;

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
     * Draft constructor.
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Api $api
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Api $api,
        LoggerInterface $logger
    )
    {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->api = $api;
        $this->logger = $logger;
    }

    public function execute()
    {
        $this->logger->debug('draft-process callback started');
        $result = $this->jsonFactory->create();

        $param = $this->getRequest()->getParam('draft_id');
        $requestParams = ['draft_id' => $param];
        $responseInfo = ['success' => true];
        $resultResponse = array_merge($responseInfo, $requestParams);
        $this->api->setAsyncOrdered($responseInfo);

        http_response_code(200);
        header('Content-Type: application/json');

        $this->logger->debug('draft-process callback finished');
        return $result->setData($resultResponse);
    }
}