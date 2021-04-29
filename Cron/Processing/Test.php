<?php
namespace Rissc\Printformer\Cron\Processing;

use Psr\Log\LoggerInterface;
use Rissc\Printformer\Cron\Processing\Runs\Test as TestRun;

/**
 * Class Test
 * @package Rissc\Printformer\Cron\Processing
 */
class Test
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var TestRun
     */
    private $testRun;

    /**
     * Test constructor.
     * @param LoggerInterface $logger
     * @param TestRun $testRun
     */
    public function __construct(
        LoggerInterface $logger,
        TestRun $testRun
    )
    {
        $this->logger = $logger;
        $this->testRun = $testRun;
    }

    /**
     * Run Cron
     */
    public function execute()
    {
        $this->logger->debug('--------------------------------Cron started--------------------------------');
        $this->testRun->execute();
        $this->logger->debug('--------------------------------Cron finished-------------------------------');
    }
}