<?php

namespace MagePackHelper\AltTextGenerator\Model\Status;

use Magento\Ui\DataProvider\AbstractDataProvider;
use MagePackHelper\AltTextGenerator\Api\AuditAggregatorInterface;
use MagePackHelper\AltTextGenerator\Api\Data\StatusInterface;
use MagePackHelper\AltTextGenerator\Model\ResourceModel\Status\CollectionFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;

class DataProvider extends AbstractDataProvider
{
    private array $loadedData = [];

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $skuMappingCollectionFactory,
        private readonly AuditAggregatorInterface $auditAggregator,
        private readonly LoggerInterface $logger,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $skuMappingCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        $this->loadedData = [];

        /* @var StatusInterface $nextItem */
        foreach ($items as $nextItem) {
            try {
                $rawContentRecord = $this->auditAggregator->getSingleRawContentRecord($nextItem);
                $id = $nextItem->getId();
                $this->loadedData[$id] = $nextItem->getData();
                $this->loadedData[$id]['content'] = $rawContentRecord['content'];
            } catch (LocalizedException $e) {
                $this->logger->error($e->getMessage());
            }
        }

        return $this->loadedData;
    }
}
