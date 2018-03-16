<?php

namespace Rissc\Printformer\Model;

class AclData
{
    /**
     * @var string
     */
    protected $action;

    /**
     * @var string
     */
    protected $entityType;

    /**
     * @var string
     */
    protected $entitiyIdentifier;

    /**
     * @var string
     */
    protected $userIdentifier;

    /**
     * @var bool
     */
    protected $allowAction;

    /**
     * AclData constructor.
     * @param string|array $data
     */
    public function __construct(
        $data = null
    ) {
        if(is_string($data)) {
            $data = json_decode($data, true);
        }
        $this->action = isset($data['action']) ? $data['action'] : '';
        $this->entityType = isset($data['entityType']) ? $data['entityType'] : '';
        $this->entitiyIdentifier = isset($data['entityIdentifier']) ? $data['entityIdentifier'] : '';
        $this->userIdentifier = isset($data['userIdentifier']) ? $data['userIdentifier'] : '';
        $this->allowAction = isset($data['allowAction']) ? $data['allowAction'] : false;
    }

    /**
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    public function toArray()
    {
        return [
            'action' => $this->getAction(),
            'entityType' => $this->getEntityType(),
            'entityIdentifier' => $this->getEntitiyIdentifier(),
            'userIdentifier' => $this->getUserIdentifier(),
            'allowAction' => $this->getAllowAction()
        ];
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     * @return $this
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return string
     */
    public function getEntityType()
    {
        return $this->entityType;
    }

    /**
     * @param string $entityType
     * @return $this
     */
    public function setEntityType($entityType)
    {
        $this->entityType = $entityType;
        return $this;
    }

    /**
     * @return string
     */
    public function getEntitiyIdentifier()
    {
        return $this->entitiyIdentifier;
    }

    /**
     * @param string $entityIdentifier
     * @return $this
     */
    public function setEntityIdentifier($entityIdentifier)
    {
        $this->entitiyIdentifier = $entityIdentifier;
        return $this;
    }

    /**
     * @return string
     */
    public function getUserIdentifier()
    {
        return $this->userIdentifier;
    }

    /**
     * @param string $userIdentifier
     * @return $this
     */
    public function setUserIdentifier($userIdentifier)
    {
        $this->userIdentifier = $userIdentifier;
        return $this;
    }

    /**
     * @return bool
     */
    public function getAllowAction()
    {
        return $this->allowAction;
    }

    /**
     * @param bool $allowAction
     * @return $this
     */
    public function setAllowAction($allowAction)
    {
        $this->allowAction = $allowAction;
        return $this;
    }
}