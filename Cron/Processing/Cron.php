<?php

namespace Rissc\Printformer\Cron\Processing;

use Psr\Log\LoggerInterface;
use Rissc\Printformer\Cron\Processing\Runs\First;
use Rissc\Printformer\Cron\Processing\Runs\Second;
use Rissc\Printformer\Cron\Processing\Runs\Third;

/**
 * Class Processing
 *
 * @package Rissc\Printformer\Cron
 */
class Cron
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var First
     */
    private $first;

    /**
     * @var Second
     */
    private $second;

    /**
     * @var Third
     */
    private $third;

    /**
     * @param   LoggerInterface  $logger
     * @param   First            $first
     * @param   Second           $second
     * @param   Third            $third
     */
    public function __construct(
        LoggerInterface $logger,
        First $first,
        Second $second,
        Third $third
    ) {
        $this->logger = $logger;
        $this->first  = $first;
        $this->second = $second;
        $this->third  = $third;
    }

    /**
     * Run Cron
     */
    public function execute()
    {
        $this->logger->debug('--------------------------------Cron started--------------------------------');
        $this->first->execute();
        $this->second->execute();
        $this->third->execute();
        $this->logger->debug('--------------------------------Cron finished-------------------------------');
    }
}
