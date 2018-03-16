<?php

namespace Rissc\Printformer\Api\Customer\Group;

interface RightRepositoryInterface
{
    /**
     * @param \Rissc\Printformer\Api\Data\Customer\Group\RightInterface $right
     * @return \Rissc\Printformer\Api\Data\Customer\Group\RightInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\Rissc\Printformer\Api\Data\Customer\Group\RightInterface $right);

    /**
     * @param int $id
     * @return \Rissc\Printformer\Api\Data\Customer\Group\RightInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return mixed
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * @param \Rissc\Printformer\Api\Data\Customer\Group\RightInterface $right
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(\Rissc\Printformer\Api\Data\Customer\Group\RightInterface $right);

    /**
     * @param int $id
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function deleteById($id);
}