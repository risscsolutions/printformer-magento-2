<?php

namespace Rissc\Printformer\Plugin\Customer;

use Magento\Customer\Model\Data\Group;
use Magento\Customer\Model\ResourceModel\GroupRepository\Interceptor;
use Rissc\Printformer\Helper\Api;

class CreateUserGroup
{
    /**
     * @var Api
     */
    private $printformerApi;

    public function __construct(
        Api $printformerApi
    ) {
        $this->printformerApi = $printformerApi;
    }

    /**
     * @param Interceptor $subject
     * @param Group $result
     * @return Group
     */
    public function afterSave(Interceptor $subject, Group $result)
    {
        $groupId = $result->getId();

        $this->printformerApi->createUserGroup($groupId);

        return $result;
    }
}
