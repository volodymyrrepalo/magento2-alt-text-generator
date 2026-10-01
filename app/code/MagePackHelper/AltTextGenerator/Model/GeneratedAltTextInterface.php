<?php

namespace MagePackHelper\AltTextGenerator\Model;

interface GeneratedAltTextInterface
{
    public const string PARENT_ID = 'parent_id';
    public const string IMAGE_SRC = 'image_src';
    public const string ALT_TEXT = 'alt_text';
    public const string IS_GLOBALLY_AVAILABLE = 'is_globally_available';

    public function getId(): mixed;

    public function getParentId(): int;
    public function setParentId(int $parentId): GeneratedAltTextInterface;

    public function getImageSrc(): string;
    public function setImageSrc(string $imageSrc): GeneratedAltTextInterface;

    public function getAltText(): string;
    public function setAltText(string $altText): GeneratedAltTextInterface;

    public function isGloballyAvailable(): bool;
    public function setIsGloballyAvailable(bool $isGloballyAvailable): GeneratedAltTextInterface;
}
