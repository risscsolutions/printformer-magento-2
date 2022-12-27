<?php

namespace Rissc\Printformer\Cron\Processing\Runs;

/**
 * Class First
 *
 * @package Rissc\Printformer\Cron\Processing\Runs
 */
class First extends Processing
{
    /**
     * Function to set dateTime filters for the sales-item-collections (3 minutes to now interval)
     *
     * @return bool
     */
    protected function setFromToFilters()
    {
        $currentDateTime = date(self::DEFAULT_DB_FORMAT);

        $toDateTime       = strtotime('-5 minutes',
            strtotime($currentDateTime));
        $this->toDateTime = date(self::DEFAULT_DB_FORMAT, $toDateTime);

        $fromDateTime       = strtotime('-35 minutes',
            strtotime($currentDateTime));
        $this->fromDateTime = date(self::DEFAULT_DB_FORMAT, $fromDateTime);

        $this->validUploadProcessingCountSmallerThen = 1;

        return true;
    }
}
