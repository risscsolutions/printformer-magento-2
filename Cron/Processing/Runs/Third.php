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
        $currentDateTime = date("Y-m-d h:i:s"); // current date
        $toDateTime = strtotime('-60 minutes', strtotime($currentDateTime));
        $this->toDateTime = date('Y-m-d h:i:s', $toDateTime);

        $fromDateTime = strtotime('-63 minutes', strtotime($currentDateTime));
        $this->fromDateTime = date('Y-m-d h:i:s', $fromDateTime); // 2 days before

        return true;
    }
}