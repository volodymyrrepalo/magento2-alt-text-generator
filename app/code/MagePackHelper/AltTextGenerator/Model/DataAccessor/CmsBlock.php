<?php

namespace MagePackHelper\AltTextGenerator\Model\DataAccessor;

use Magento\Framework\App\ResourceConnection;
use MagePackHelper\AltTextGenerator\Api\Data\StatusInterface;
use MagePackHelper\AltTextGenerator\Api\DataAccessorInterface;

class CmsBlock implements DataAccessorInterface
{
    private const string TARGET_TYPE = 'cms_block';
    private const string TABLE_NAME = 'cms_block';

    public function __construct(
        private readonly ResourceConnection $resource,
    ) {
    }

    public function getTargetType(): string
    {
        return self::TARGET_TYPE;
    }

    public function getRawContentRecords(?StatusInterface $status = null): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select();
        $select->from(
            $connection->getTableName(self::TABLE_NAME),
            ['block_id', 'content']
        )
            ->where('is_active = ?', '1');

        if ($status) {
            $select->where('block_id = ?', $status->getTargetId());
        }
        if (!$status) {
            // A full scan only opens records that contain an image. Reloading one record does not use this filter.
            $select->where('content like ?', '%<img%');
        }

        $result = [];
        foreach ($connection->fetchAll($select) as $nextRow) {
            $result[] = [
                'id' => $nextRow['block_id'],
                'owner_id' => $nextRow['block_id'],
                // Block content is not stored per store view, so the status row uses store 0.
                'store_id' => 0,
                'content' => $nextRow['content'],
            ];
        }

        return $result;
    }

    public function saveContent(string $content, StatusInterface $status): void
    {
        $connection = $this->resource->getConnection();
        $connection->update(
            $connection->getTableName(self::TABLE_NAME),
            ['content' => $content],
            ['block_id = ?' => $status->getTargetId()]
        );
    }
}
