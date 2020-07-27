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
        $this->toDateTime = date("Y-m-d h:i:s"); // current date
        $fromDateTime = strtotime('-3 minutes', strtotime($this->toDateTime));
        $this->fromDateTime = date('Y-m-d h:i:s', $fromDateTime); // 2 days before

        return true;
    }
}