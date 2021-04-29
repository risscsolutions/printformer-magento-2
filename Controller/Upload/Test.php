<?php
namespace Rissc\Printformer\Controller\Upload;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\State;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Rissc\Printformer\Cron\Processing\Test as CronTest;

/**
 * Test class for cron
 *
 * Class Index
 * @package Rissc\Printformer\Controller\Upload
 */
class Test extends Action
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var Cron
     */
    private $cronTest;

    /**
     * @var State
     */
    private $state;

    /**
     * Index constructor.
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Cron $cronTest
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CronTest $cronTest,
        State $state
    )
    {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->cronTest = $cronTest;
        $this->state = $state;
    }

    /**
     * Test class to simulate cron (async process to test will run only in developer mode)
     *
     * @return Json $result
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        if($this->state->getMode() == $this->state::MODE_DEVELOPER){
            $this->cronTest->execute();
        }

        http_response_code(200);
        header('Content-Type: application/json');

        return $result;
    }
}