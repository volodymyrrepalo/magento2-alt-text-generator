<?php

namespace MagePackHelper\AltTextGenerator\Api;

use Magento\Framework\Exception\NotFoundException;
use MagePackHelper\AltTextGenerator\Api\Data\StatusInterface;

interface StatusRepositoryInterface
{
    /**
     * @throws NotFoundException
     */
    public function getById(int $id): StatusInterface;

    public function getByTargetData(string $targetType, int $targetId, int $storeId): StatusInterface;

    public function save(StatusInterface $status): StatusInterface;

    public function delete(int $id): void;
}
