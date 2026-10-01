<?php

namespace MagePackHelper\AltTextGenerator\Model\ResourceModel\Status\Grid;

use MagePackHelper\AltTextGenerator\Model\ResourceModel\Status\Collection as StatusCollection;
use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;
use Psr\Log\LoggerInterface;

class Collection extends StatusCollection implements SearchResultInterface
{
    /**
     * @var AggregationInterface
     */
    protected $aggregations;
    /** @var mixed */
    private $model;
    /** @var string */
    private $resourceModel;

    public function __construct(
        $mainTable,
        $eventPrefix,
        $eventObject,
        $resourceModel,
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        ?AdapterInterface $connection = null,
        ?AbstractDb $resource = null,
        $model = Document::class,
    ) {
        $this->resourceModel = $resourceModel;
        $this->model = $model;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->_eventPrefix = $eventPrefix;
        $this->_eventObject = $eventObject;
        $this->_init($this->model, $this->resourceModel);
        $this->setMainTable($mainTable);
    }

    public function setItems(?array $items = null)
    {
        return $this;
    }

    public function getAggregations()
    {
        return $this->aggregations;
    }

    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
        return $this;
    }

    public function getSearchCriteria()
    {
        return null;
    }

    public function setSearchCriteria(?SearchCriteriaInterface $searchCriteria = null)
    {
        return $this;
    }

    public function getTotalCount()
    {
        return $this->getSize();
    }

    public function setTotalCount($totalCount)
    {
        return $this;
    }
}
