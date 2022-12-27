<?php

namespace Rissc\Printformer\Cron\Processing;

use Psr\Log\LoggerInterface;
use Rissc\Printformer\Cron\Processing\Runs\Test;

/**
 * Class Processing
 *
 * @package Rissc\Printformer\Cron
 */
class CronTest
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Test
     */
    private $test;

    /**
     * @param   LoggerInterface  $logger
     * @param   Test             $test
     */
    public function __construct(
        LoggerInterface $logger,
        Test $test
    ) {
        $this->logger = $logger;
        $this->test   = $test;
    }

    /**
     * Run Cron
     */
    public function execute()
    {
        $this->logger->debug('--------------------------------Cron started--------------------------------');
        $this->test->execute();
        $this->logger->debug('--------------------------------Cron finished-------------------------------');
    }
}
