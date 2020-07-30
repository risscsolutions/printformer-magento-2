<?php
namespace Rissc\Printformer\Cron\Processing\Runs;

/**
 * Class First
 * @package Rissc\Printformer\Cron\Processing\Runs
 */
class First extends Processing
{
    /**
     * Function to set dateTime filters for the sales-item-collections (3 minutes to now interval)
     * @return bool
     */
    protected function setFromToFilters()
    {
        $this->toDateTime = date(self::DEFAULT_DB_FORMAT);
        $fromDateTime = strtotime('-1 minutes', strtotime($this->toDateTime));
        $this->fromDateTime = date(self::DEFAULT_DB_FORMAT, $fromDateTime);

        return true;
    }
}