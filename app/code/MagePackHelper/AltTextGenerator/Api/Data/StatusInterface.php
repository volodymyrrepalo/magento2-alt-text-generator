<?php

namespace MagePackHelper\AltTextGenerator\Api\Data;

use MagePackHelper\AltTextGenerator\Model\GeneratedAltTextInterface;

interface StatusInterface
{
    public const string TARGET_TYPE = 'target_type';
    public const string TARGET_ID = 'target_id';
    public const string STORE_ID = 'store_id';
    public const string UPDATED_AT = 'updated_at';
    public const string MISSING_ALT_TEXT_COUNT = 'missing_alt_text_count';
    public const string GENERATED_ALT_TEXT_COUNT = 'generated_alt_text_count';
    public const string STATE = 'state';
    public const string GENERATED_ALT_TEXTS = 'generated_alt_texts';
    public const string SEO_TEXT = 'seo_text';
    public const string SCAN_INFO = 'scan_info';
    public const string GENERATION_INFO = 'generation_info';
    public const string ALT_TEXT_GENERATION_ALLOWED = 'alt_text_generation_allowed';

    public function getId(): mixed;

    public function getTargetType(): string;
    public function setTargetType(string $targetType): StatusInterface;

    public function getTargetId(): int;
    public function setTargetId(int $targetId): StatusInterface;

    public function getStoreId(): int;
    public function setStoreId(int $storeId): StatusInterface;

    public function getMissingAltTextCount(): int;
    public function setMissingAltTextCount(int $count): StatusInterface;

    public function getGeneratedAltTextCount(): int;
    public function setGeneratedAltTextCount(array $altTexts): StatusInterface;

    public function getState(): string;
    public function setState(string $state): StatusInterface;

    public function findGeneratedAltText(string $imageSrc): string;
    /**
     * @return GeneratedAltTextInterface[]
     */
    public function getGeneratedAltTexts(): array;
    public function setGeneratedAltTexts(array $altTexts): StatusInterface;
    public function addGeneratedAltText(string $imageSrc, string $altText): StatusInterface;

    public function getSeoText(): string;
    public function setSeoText(string $seoText): StatusInterface;

    public function getScanInfo(): string;
    public function setScanInfo(string $info): StatusInterface;
    public function addScanInfo(string $info): StatusInterface;

    public function getGenerationInfo(): string;
    public function setGenerationInfo(string $info): StatusInterface;
    public function addGenerationInfo(string $info): StatusInterface;

    public function getImageToAltTextMap(): array;

    public function isAltTextGenerationAllowed(): bool;
    public function setAltTextGenerationAllowed(bool $allowed): StatusInterface;
}
