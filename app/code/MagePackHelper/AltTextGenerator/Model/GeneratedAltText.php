<?php

namespace MagePackHelper\AltTextGenerator\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use MagePackHelper\AltTextGenerator\Model\ResourceModel\GeneratedAltText as GeneratedAltTextResource;

class GeneratedAltText extends AbstractModel implements GeneratedAltTextInterface
{
    /**
     * @throws LocalizedException
     */
    protected function _construct()
    {
        $this->_init(GeneratedAltTextResource::class);
    }

    public function getParentId(): int
    {
        return $this->getData(GeneratedAltTextInterface::PARENT_ID) ?: 0;
    }

    public function setParentId(int $parentId): GeneratedAltTextInterface
    {
        $this->setData(GeneratedAltTextInterface::PARENT_ID, $parentId);

        return $this;
    }

    public function getImageSrc(): string
    {
        return $this->getData(GeneratedAltTextInterface::IMAGE_SRC) ?: '';
    }

    public function setImageSrc(string $imageSrc): GeneratedAltTextInterface
    {
        $this->setData(GeneratedAltTextInterface::IMAGE_SRC, $imageSrc);

        return $this;
    }

    public function getAltText(): string
    {
        return $this->getData(GeneratedAltTextInterface::ALT_TEXT) ?: '';
    }

    public function setAltText(string $altText): GeneratedAltTextInterface
    {
        $this->setData(GeneratedAltTextInterface::ALT_TEXT, $altText);

        return $this;
    }

    public function isGloballyAvailable(): bool
    {
        return (bool)$this->getData(GeneratedAltTextInterface::IS_GLOBALLY_AVAILABLE);
    }

    public function setIsGloballyAvailable(bool $isGloballyAvailable): GeneratedAltTextInterface
    {
        $this->setData(GeneratedAltTextInterface::IS_GLOBALLY_AVAILABLE, $isGloballyAvailable);

        return $this;
    }
}
