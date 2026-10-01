<?php

namespace MagePackHelper\AltTextGenerator\Api;

use MagePackHelper\AltTextGenerator\Api\Data\StatusInterface;

interface DataAccessorInterface
{
    public function getTargetType(): string;
    public function getRawContentRecords(?StatusInterface $status = null): array;
    public function saveContent(string $content, StatusInterface $status): void;
}
