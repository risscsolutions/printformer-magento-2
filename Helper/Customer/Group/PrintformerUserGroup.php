<?php

namespace Rissc\Printformer\Helper\Customer\Group;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Rissc\Printformer\Model\PrintformerUserGroupFactory;
use Rissc\Printformer\Model\ResourceModel\PrintformerUserGroup as UserGroupResource;
use Rissc\Printformer\Model\ResourceModel\PrintformerUserGroup\CollectionFactory as UserGroupCollectionFactory;

class PrintformerUserGroup extends AbstractHelper
{
    /**
     * @var UserGroupCollectionFactory
     */
    protected $userGroupCollectionFactory;

    /**
     * @var PrintformerUserGroupFactory
     */
    protected $printformerUserGroupFactory;

    /**
     * @var UserGroupResource
     */
    protected $printformerUserGroupResource;

    public function __construct(
        UserGroupCollectionFactory $userGroupCollectionFactory,
        PrintformerUserGroupFactory $printformerUserGroupFactory,
        UserGroupResource $printformerUserGroupResource,
        Context $context
    ) {
        $this->userGroupCollectionFactory = $userGroupCollectionFactory;
        $this->printformerUserGroupFactory = $printformerUserGroupFactory;
        $this->printformerUserGroupResource = $printformerUserGroupResource;
        parent::__construct($context);
    }


    public function getUserGroupIdentifier(int $magentoGroupId): ?string
    {
        $userGroupCollection = $this->userGroupCollectionFactory->create();
        $group = $userGroupCollection->addFilter('magento_user_group_id', $magentoGroupId)
            ->getFirstItem();

        return $group->getPrintformerUserGroupId();
    }

    public function createUserGroup(int $magentoGroupId, string $printformerGroupId): void
    {
        $printformerUserGroup = $this->printformerUserGroupFactory->create();
        $printformerUserGroup->setData([
            'magento_user_group_id' => $magentoGroupId,
            'printformer_user_group_id' => $printformerGroupId
        ]);

        $this->printformerUserGroupResource->save($printformerUserGroup);
    }

    public function deleteUserGroup(int $magentoGroupId): void
    {
        $connection = $this->printformerUserGroupResource->getConnection();
        $userGroupTableName = $this->printformerUserGroupResource->getMainTable();

        $where = [
            'magento_user_group_id = ?' => $magentoGroupId
        ];

        $connection->delete($userGroupTableName, $where);
    }
}