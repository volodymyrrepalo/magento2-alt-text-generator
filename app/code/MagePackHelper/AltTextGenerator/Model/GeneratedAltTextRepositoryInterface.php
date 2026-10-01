<?php

namespace MagePackHelper\AltTextGenerator\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;

interface GeneratedAltTextRepositoryInterface
{
    public function getById(int $id): GeneratedAltTextInterface;
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    public function save(GeneratedAltTextInterface $generatedAltText): GeneratedAltTextInterface;

    public function delete(int $id): void;

}
