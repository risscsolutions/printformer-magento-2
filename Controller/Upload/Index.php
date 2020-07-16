<?php
namespace Rissc\Printformer\Controller\Upload;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Rissc\Printformer\Cron\Process;

/**
 * Test class for cron
 *
 * Class Index
 * @package Rissc\Printformer\Controller\Upload
 */
class Index extends Action
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var Process
     */
    private $process;

    /**
     * Index constructor.
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Process $process
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Process $process
    )
    {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->process = $process;
    }

    /**
     * Test class to simulate cron
     *
     * @return Json $result
     */
    public function execute()
    {
        $this->process->execute();
//        $data = $this->_request->getParams();
        $data = array();
        $result = $this->jsonFactory->create();
        $result->setData($data);
        return $result;
    }
}