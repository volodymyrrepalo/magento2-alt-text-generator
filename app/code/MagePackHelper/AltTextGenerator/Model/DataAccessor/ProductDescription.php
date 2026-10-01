<?php

namespace MagePackHelper\AltTextGenerator\Model\DataAccessor;

use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use MagePackHelper\AltTextGenerator\Api\Data\StatusInterface;
use MagePackHelper\AltTextGenerator\Api\DataAccessorInterface;

class ProductDescription implements DataAccessorInterface
{
    private const string TARGET_TYPE = 'product_description';
    private const string TABLE_NAME = 'catalog_product_entity_text';
    private const string DESCRIPTION_ATTRIBUTE_CODE = 'description';

    private int $descriptionAttributeId = 0;

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly EavConfig $eavConfig,
    ) {
    }

    public function getTargetType(): string
    {
        return self::TARGET_TYPE;
    }

    /**
     * @throws LocalizedException
     */
    public function getRawContentRecords(?StatusInterface $status = null): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select();
        $select->from(
            $connection->getTableName(self::TABLE_NAME),
            ['value_id', 'store_id', 'entity_id', 'value']
        )
            ->where('attribute_id = ?', $this->getDescriptionAttributeId())
            // Store view 1 is excluded from product-description scans.
            ->where('store_id <> ?', '1');

        if ($status) {
            $select
                ->where('store_id = ?', $status->getStoreId())
                ->where('entity_id = ?', $status->getTargetId());
        }
        if (!$status) {
            // A full scan only opens records that contain an image. Reloading one record does not use this filter.
            $select->where('value like ?', '%<img%');
        }

        $result = [];
        foreach ($connection->fetchAll($select) as $nextRow) {
            $result[] = [
                'id' => $nextRow['value_id'],
                'owner_id' => $nextRow['entity_id'],
                'store_id' => $nextRow['store_id'],
                'content' => $nextRow['value'],
            ];
        }

        return $result;
    }

    /**
     * @throws LocalizedException
     */
    public function saveContent(string $content, StatusInterface $status): void
    {
        $connection = $this->resource->getConnection();
        $connection->update(
            $connection->getTableName(self::TABLE_NAME),
            ['value' => $content],
            [
                'entity_id = ?' => $status->getTargetId(),
                'attribute_id = ?' => $this->getDescriptionAttributeId(),
                'store_id = ?' => $status->getStoreId(),
            ]
        );
    }

    /**
     * @throws LocalizedException
     */
    private function getDescriptionAttributeId(): int
    {
        if ($this->descriptionAttributeId === 0) {
            $this->descriptionAttributeId =
                $this->eavConfig->getAttribute(
                    'catalog_product',
                    self::DESCRIPTION_ATTRIBUTE_CODE
                )->getAttributeId();
        }
        if (empty($this->descriptionAttributeId)) {
            throw new LocalizedException(__('Description attribute not found'));
        }

        return $this->descriptionAttributeId;
    }
}
