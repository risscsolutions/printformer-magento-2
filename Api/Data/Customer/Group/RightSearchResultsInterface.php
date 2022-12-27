<?php

namespace Rissc\Printformer\Api\Data\Customer\Group;

use Magento\Framework\Api\SearchResultsInterface;

interface RightSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get group right list.
     *
     * @return \Rissc\Printformer\Api\Data\Customer\Group\RightInterface[]
     */
    public function getItems();

    /**
     * Set group right list.
     *
     * @param   \Rissc\Printformer\Api\Data\Customer\Group\RightInterface[]  $items
     *
     * @return $this
     */
    public function setItems(array $items);
}
