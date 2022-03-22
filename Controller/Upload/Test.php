<?php
namespace Rissc\Printformer\Controller\Upload;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\State;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Rissc\Printformer\Cron\Processing\CronTest;

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
     * @var State
     */
    private $state;

    /**
     * @var CronTest
     */
    private $cronTest;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param CronTest $cronTest
     * @param State $state
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
        $this->state = $state;
        $this->cronTest = $cronTest;
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