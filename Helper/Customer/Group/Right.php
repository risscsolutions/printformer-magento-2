<?php

namespace Rissc\Printformer\Helper\Customer\Group;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Customer\Model\Group;
use Magento\Framework\App\Helper\Context;
use Rissc\Printformer\Api\Data\Customer\Group\RightInterface;
use Rissc\Printformer\Model\Customer\Group\RightRepository;
use Rissc\Printformer\Model\Customer\Group\RightFactory;

class Right extends AbstractHelper
{
    /**
     * Array to store internal rights for helper (key is right id)
     * @var array
     */
    protected $rights = null;

    /**
     * Array to store right associated with customer group (key is group id)
     * @var array
     */
    protected $right = [];

    /**
     * @var RightFactory
     */
    protected $rightFactory;

    /**
     * @var RightRepository
     */
    protected $rightRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Right constructor.
     * @param RightFactory $rightFactory
     * @param RightRepository $rightRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Context $context
     */
    public function __construct(
        RightFactory $rightFactory,
        RightRepository $rightRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Context $context
    ) {
        $this->rightFactory = $rightFactory;
        $this->rightRepository = $rightRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct($context);
    }

    /**
     * @param Group $group
     * @return RightInterface
     */
    public function getRight(Group $group)
    {
        if(!isset($this->right[$group->getCustomerGroupId()])) {
            foreach($this->getRights() as $right) {
                if($right->getCustomerGroupId() == $group->getCustomerGroupId()) {
                    $this->right[$group->getCustomerGroupId()] = $right;
                    break;
                }
            }

            if(!isset($this->right[$group->getCustomerGroupId()])) {
                // Create empty one in case no right is found
                $right = $this->rightFactory->create()->reset();
                $right->setCustomerGroupId($group->getCustomerGroupId());
                $right = $this->rightRepository->save($right);
                $this->rights[$right->getId()] = $right;
                $this->right[$group->getCustomerGroupId()];
            }
        }
        return $this->right[$group->getCustomerGroupId()];
    }

    /**
     * Reset all rights (all rights = false)
     */
    public function resetRights()
    {
        foreach($this->getRights() as $right) {
            $right->reset();
        }
    }

    /**
     * Save all loaded rights
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function saveRights()
    {
        foreach($this->getRights() as $right) {
            $this->rightRepository->save($right);
        }
    }

    /**
     * @return array
     */
    public function getRights()
    {
        if($this->rights === null) {
            $this->rights = [];
            $searchCriteria = $this->searchCriteriaBuilder->create();
            foreach($this->rightRepository->getList($searchCriteria)->getItems() as $right) {
                $this->rights[$right->getId()] = $right;
            }
        }
        return $this->rights;
    }
}