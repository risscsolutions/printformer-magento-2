<?php

namespace Rissc\Printformer\Controller\Test;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\State;
use Psr\Log\LoggerInterface;
use Rissc\Printformer\Cron\CleanDesignImages as Test;

class CleanDesignImages extends Action
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Test $test
     */
    private $test;

    /**
     * @var State $state
     */
    private State $state;

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        Test $test,
        State $state
    )
    {
        $this->logger = $logger;
        $this->test = $test;
        $this->state = $state;
        parent::__construct($context);
    }

    /**
     * Run Cron
     */
    public function execute()
    {
        $this->logger->debug('--------------------------------Cron cleanDesignImages-test started--------------------------------');

        if ($this->state->getMode() == $this->state::MODE_DEVELOPER) {
            $this->test->execute();
        }

        $this->logger->debug('--------------------------------Cron cleanDesignImages-test finished-------------------------------');
    }
}