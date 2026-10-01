<?php

namespace MagePackHelper\AltTextGenerator\Model\DataAccessor;

use Magento\Framework\App\ResourceConnection;
use MagePackHelper\AltTextGenerator\Api\Data\StatusInterface;
use MagePackHelper\AltTextGenerator\Api\DataAccessorInterface;

class CmsPage implements DataAccessorInterface
{
    private const string TARGET_TYPE = 'cms_page';
    private const string TABLE_NAME = 'cms_page';

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
            ['page_id', 'content']
        )
            ->where('is_active = ?', '1');

        if ($status) {
            $select->where('page_id = ?', $status->getTargetId());
        }
        if (!$status) {
            // A full scan only opens records that contain an image. Reloading one record does not use this filter.
            $select->where('content like ?', '%<img%');
        }

        $result = [];
        foreach ($connection->fetchAll($select) as $nextRow) {
            $result[] = [
                'id' => $nextRow['page_id'],
                'owner_id' => $nextRow['page_id'],
                // Page content is not stored per store view, so the status row uses store 0.
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
            ['page_id = ?' => $status->getTargetId()]
        );
    }
}
