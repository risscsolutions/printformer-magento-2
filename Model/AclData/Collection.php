<?php

namespace Rissc\Printformer\Model\AclData;

use Rissc\Printformer\Model\AclData;

class Collection
{
    /**
     * @var array
     */
    protected $collection = [];

    /**
     * Initialize collection
     */
    public function __construct()
    {
        $this->collection = [];
    }

    /**
     * @param   AclData  $item
     *
     * @return $this
     */
    public function addItem(AclData $item)
    {
        $this->collection[] = $item;

        return $this;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->collection;
    }

    /**
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $result = ['actions' => []];

        foreach ($this->collection as $item) {
            $result['actions'][] = $item->toArray();
        }

        return $result;
    }
}
