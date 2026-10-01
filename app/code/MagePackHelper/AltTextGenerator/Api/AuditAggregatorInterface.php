<?php

namespace MagePackHelper\AltTextGenerator\Api;

use Magento\Framework\Exception\LocalizedException;
use MagePackHelper\AltTextGenerator\Api\Data\StatusInterface;

interface AuditAggregatorInterface
{
    public function scan(int $statusId = 0): void;
    public function generate(int $statusId): void;
    public function fix(int $statusId): void;
    public function updateContent(string $content, int $statusId): void;
    /**
     * @throws LocalizedException
     */
    public function getSingleRawContentRecord(StatusInterface $status): array;
}
