<?php
namespace Rissc\Printformer\Controller\Upload;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\State;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Rissc\Printformer\Cron\Processing\Cron;

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
     * @var Cron
     */
    private $cron;
    /**
     * @var State
     */
    private $state;

    /**
     * Index constructor.
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Cron $cron
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Cron $cron,
        State $state
    )
    {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->cron = $cron;
        $this->state = $state;
    }

    /**
     * Test class to simulate cron (async process to test will run only in developer mode)
     *
     * @return Json $result
     */
    public function execute()
    {
        if($this->state->getMode() == $this->state::MODE_DEVELOPER){
            $this->cron->execute();
            //todo: possible to implement test-values
        }
        $data = array();
        $result = $this->jsonFactory->create();
        $result->setData($data);
        return $result;
    }
}