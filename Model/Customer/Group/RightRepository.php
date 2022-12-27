<?php

namespace Rissc\Printformer\Model\Customer\Group;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Rissc\Printformer\Api\Customer\Group\RightRepositoryInterface;
use Rissc\Printformer\Api\Data\Customer\Group\RightInterface;
use Rissc\Printformer\Api\Data\Customer\Group\RightSearchResultsInterfaceFactory;
use Rissc\Printformer\Model\Customer\Group\RightFactory;
use Rissc\Printformer\Model\Customer\Resource\Group\Right as ResourceRight;
use Rissc\Printformer\Model\Customer\Resource\Group\Right\CollectionFactory as ResourceCollectionFactory;

class RightRepository implements RightRepositoryInterface
{
    /**
     * @var ResourceRight
     */
    protected $resource;

    /**
     * @var RightFactory
     */
    protected $rightFactory;

    /**
     * @var ResourceCollectionFactory
     */
    protected $resourceCollectionFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var RightSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * RightRepository constructor.
     *
     * @param   ResourceRight                       $resource
     * @param   RightFactory                        $rightFactory
     * @param   ResourceCollectionFactory           $resourceCollectionFactory
     * @param   RightSearchResultsInterfaceFactory  $searchResultsFactory
     * @param   CollectionProcessorInterface        $collectionProcessor
     */
    public function __construct(
        ResourceRight $resource,
        RightFactory $rightFactory,
        ResourceCollectionFactory $resourceCollectionFactory,
        RightSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource                  = $resource;
        $this->rightFactory              = $rightFactory;
        $this->resourceCollectionFactory = $resourceCollectionFactory;
        $this->searchResultsFactory      = $searchResultsFactory;
        $this->collectionProcessor       = $collectionProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function save(RightInterface $right)
    {
        try {
            $this->resource->save($right);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the resource: %1', $exception->getMessage()),
                $exception
            );
        }

        return $right;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    ) {
        /** @var \Rissc\Printformer\Model\Customer\Resource\Group\Right\Collection $collection */
        $collection = $this->resourceCollectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var \Rissc\Printformer\Api\Data\Customer\Group\RightSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }

    /**
     * {@inheritdoc}
     */
    public function delete(RightInterface $right)
    {
        try {
            $this->resource->delete($right);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the right: %1',
                $exception->getMessage()
            ));
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($id)
    {
        $right = $this->rightFactory->create();
        $right->load($id);
        if (!$right->getId()) {
            throw new NoSuchEntityException(__('Right with id "%1" does not exist.',
                $id));
        }

        return $right;
    }
}
