<?php
namespace Rissc\Printformer\Cron\Processing\Runs;

/**
 * Class Third
 * @package Rissc\Printformer\Cron\Processing\Runs
 */
class Third extends Processing
{
    /**
     * Function to set dateTime filters for the sales-item-collections (63 minutes ago to 60 minutes ago)
     * @return bool
     */
    protected function setFromToFilters()
    {
        $currentDateTime = date(self::DEFAULT_DB_FORMAT);

        $toDateTime = strtotime('-60 minutes', strtotime($currentDateTime));
        $this->toDateTime = date(self::DEFAULT_DB_FORMAT, $toDateTime);

        $fromDateTime = strtotime('-63 minutes', strtotime($currentDateTime));
        $this->fromDateTime = date(self::DEFAULT_DB_FORMAT, $fromDateTime);

        return true;
    }
}