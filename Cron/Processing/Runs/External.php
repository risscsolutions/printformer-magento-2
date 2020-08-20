<?php
namespace Rissc\Printformer\Cron\Processing\Runs;

/**
 * Class External
 * @package Rissc\Printformer\Cron\Processing\Runs
 */
class External extends Processing
{
    /**
     * Function to set dateTime filters for the sales-item-collections (no datetime filter in this case)
     * @return bool
     */
    protected function setFromToFilters()
    {
        return false;
    }

    /**
     * @param string $orderItemIdsToFilter
     */
    public function setOrderItemIdsToFilter($orderItemIdsToFilter)
    {
        $this->orderItemIdsToFilter = $orderItemIdsToFilter;
    }

    /**
     * Execute without day-filters and without cron specific logic
     */
    public function execute()
    {
        $this->resetProcessingFilters();

        $this->uploadPrintformerOrderUploadItems();
    }
}