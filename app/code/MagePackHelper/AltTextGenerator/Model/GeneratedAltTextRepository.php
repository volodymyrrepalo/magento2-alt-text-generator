<?php

namespace MagePackHelper\AltTextGenerator\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NotFoundException;
use MagePackHelper\AltTextGenerator\Model\ResourceModel\GeneratedAltText as GeneratedAltTextResource;
use MagePackHelper\AltTextGenerator\Model\ResourceModel\GeneratedAltText\CollectionFactory as GeneratedAltTextCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;

class GeneratedAltTextRepository implements GeneratedAltTextRepositoryInterface
{
    public function __construct(
        private readonly GeneratedAltTextInterfaceFactory $generatedAltTextFactory,
        private readonly GeneratedAltTextCollectionFactory $generatedAltTextCollectionFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory,
        private readonly GeneratedAltTextResource $generatedAltTextResource,
    ) {
    }

    /**
     * @throws NotFoundException
     */
    public function getById(int $id): GeneratedAltTextInterface
    {
        $model = $this->generatedAltTextFactory->create();
        $this->generatedAltTextResource->load($model, $id);
        if (!$model->getId()) {
            throw new NotFoundException(__('Generated Alt Text record with ID %1 does not exist', $id));
        }

        return $model;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->generatedAltTextCollectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @throws AlreadyExistsException
     */
    public function save(GeneratedAltTextInterface $generatedAltText): GeneratedAltTextInterface
    {
        $this->generatedAltTextResource->save($generatedAltText);

        return $generatedAltText;
    }

    /**
     * @throws NotFoundException
     * @throws \Exception
     */
    public function delete(int $id): void
    {
        $model = $this->getById($id);
        $this->generatedAltTextResource->delete($model);
    }
}
