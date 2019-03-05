<?php
namespace Rissc\Printformer\Model\Api\Webservice\Data;

/**
 * Interface AclDataInterface
 * @package Rissc\Printformer\Model\Api\Webservice\Data
 */
interface AclDataInterface
{
    /**
     * @return \Rissc\Printformer\Model\AclData[]
     */
    public function getActions();

    /**
     * @param array $data
     *
     * @return \Rissc\Printformer\Model\Api\Webservice\Service\AclData
     */
    public function setActions(array $data);
}