<?php

namespace Rissc\Printformer\Model\Api\Webservice\Service;

use Rissc\Printformer\Model\Api\Webservice\Data\AclDataInterface;

/**
 * Class AclData
 *
 * @package Rissc\Printformer\Model\Api\Webservice\Service
 */
class AclData implements AclDataInterface
{
    /**
     * @var array
     */
    protected $_data = [];

    /**
     * @param   array  $data
     *
     * @return AclData
     */
    public function setActions(array $data)
    {
        $this->_data = $data;

        return $this;
    }

    /**
     * @return \Rissc\Printformer\Model\AclData[]
     */
    public function getActions()
    {
        return $this->_data;
    }
}
