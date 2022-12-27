<?php

namespace Rissc\Printformer\Api\Data;

/**
 * Interface AclDataInterface
 *
 * @package Rissc\Printformer\Api\Data
 */
interface AclDataInterface
{
    /**
     * @return string
     */
    public function toJson();

    /**
     * @return array
     */
    public function toArray();

    /**
     * @return string
     */
    public function getAction();

    /**
     * @param   string  $action
     *
     * @return $this
     */
    public function setAction($action);

    /**
     * @return string
     */
    public function getEntityType();

    /**
     * @param   string  $entityType
     *
     * @return $this
     */
    public function setEntityType($entityType);

    /**
     * @return string
     */
    public function getEntityIdentifier();

    /**
     * @param   string  $entityIdentifier
     *
     * @return $this
     */
    public function setEntityIdentifier($entityIdentifier);

    /**
     * @return string
     */
    public function getUserIdentifier();

    /**
     * @param   string  $userIdentifier
     *
     * @return $this
     */
    public function setUserIdentifier($userIdentifier);

    /**
     * @return bool
     */
    public function getAllowAction();

    /**
     * @param   bool  $allowAction
     *
     * @return $this
     */
    public function setAllowAction($allowAction);
}
