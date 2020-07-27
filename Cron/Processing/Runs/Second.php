<?php
namespace Rissc\Printformer\Cron\Processing\Runs;

/**
 * Class Second
 * @package Rissc\Printformer\Cron\Processing\Runs
 */
class Second extends Processing
{
    /**
     * Function to set dateTime filters for the sales-item-collections (18 minutes ago to 15 minutes ago)
     * @return bool
     */
    protected function setFromToFilters()
    {
        $currentDateTime = date("Y-m-d h:i:s"); // current date
        $toDateTime = strtotime('-15 minutes', strtotime($currentDateTime));
        $this->toDateTime = date('Y-m-d h:i:s', $toDateTime);

        $fromDateTime = strtotime('-18 minutes', strtotime($currentDateTime));
        $this->fromDateTime = date('Y-m-d h:i:s', $fromDateTime); // 2 days before

        return true;
    }
}