<?php
namespace Rissc\Printformer\Cron\Processing\Runs;

/**
 * Class Test
 * @package Rissc\Printformer\Cron\Processing\Runs
 */
class Test extends Processing
{
    /**
     * Function to set dateTime filters for the sales-item-collections (18 minutes ago to 15 minutes ago)
     * @return bool
     */
    protected function setFromToFilters()
    {
        $currentDateTime = date(self::DEFAULT_DB_FORMAT);

        $toDateTime = strtotime('+120 minutes', strtotime($currentDateTime));
        $this->toDateTime = date(self::DEFAULT_DB_FORMAT, $toDateTime);

        $fromDateTime = strtotime('+117 minutes', strtotime($currentDateTime));
        $this->fromDateTime = date(self::DEFAULT_DB_FORMAT, $fromDateTime);

        return true;
    }
}