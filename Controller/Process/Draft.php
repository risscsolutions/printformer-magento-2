<?php
namespace Rissc\Printformer\Controller\Process;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
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
     * Draft constructor.
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Api $api
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Api $api
    )
    {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->api = $api;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        $param = $this->getRequest()->getParam('draft_id');
        $draftToSync = [];
        array_push($draftToSync, $param);
        $this->api->setAsyncOrdered($draftToSync);

        http_response_code(200);
        header('Content-Type: application/json');

        return $result->setData($draftToSync);
    }
}