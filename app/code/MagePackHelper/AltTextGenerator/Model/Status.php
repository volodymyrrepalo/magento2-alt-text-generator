<?php

namespace MagePackHelper\AltTextGenerator\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use MagePackHelper\AltTextGenerator\Api\Data\StatusInterface;
use MagePackHelper\AltTextGenerator\Model\ResourceModel\Status as StatusResource;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use MagePackHelper\AltTextGenerator\Api\AuditAggregatorInterface;

/**
 * Tracks missing alt text for one content record and the descriptions already generated for its images.
 */
class Status extends AbstractModel implements StatusInterface
{
    /**
     * Prefix ChatGPT is instructed to use for small decorative images.
     * Those descriptions are reused across content records.
     */
    private const string SIMPLE_SYMBOL_INDICATION = 'Simple symbol.';

    public function __construct(
        Context $context,
        Registry $registry,
        private readonly GeneratedAltTextRepositoryInterface $generatedAltTextRepository,
        private readonly GeneratedAltTextInterfaceFactory $generatedAltTextFactory,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly FilterBuilder $filterBuilder,
        private readonly AuditAggregatorInterface $auditAggregator,
    ) {
        parent::__construct($context, $registry);
    }

    /**
     * @throws LocalizedException
     */
    protected function _construct()
    {
        $this->_init(StatusResource::class);
    }

    public function afterSave()
    {
        $this->saveGeneratedAltTexts();

        return parent::afterSave();
    }

    public function findGeneratedAltText(string $imageSrc): string
    {
        foreach ($this->getGeneratedAltTexts() as $generatedAltTextModel) {
            if ($generatedAltTextModel->getImageSrc() == $imageSrc) {
                return $generatedAltTextModel->getAltText();
            }
        }

        return '';
    }

    /**
     * @return GeneratedAltTextInterface[]
     */
    public function getGeneratedAltTexts(): array
    {
        $result = $this->getData(StatusInterface::GENERATED_ALT_TEXTS) ?: [];
        if (empty($result)) {
            // addFilters() is OR: texts saved for this record, plus texts marked reusable for every record.
            $itemList = $this->generatedAltTextRepository->getList(
                $this->searchCriteriaBuilder
                    ->addFilters([
                        $this->filterBuilder->setField('is_globally_available')->setValue(1)->create(),
                        $this->filterBuilder->setField('parent_id')->setValue($this->getId())->create(),
                    ])
                    ->create()
            )->getItems();

            /** @var \MagePackHelper\AltTextGenerator\Model\GeneratedAltTextInterface $nextItem */
            foreach ($itemList as $nextItem) {
                $result[$nextItem->getImageSrc()] = $nextItem;
            }

            $this->setGeneratedAltTexts($result);
        }

        return $result;
    }

    public function setGeneratedAltTexts(array $altTexts): StatusInterface
    {
        $this->setData(StatusInterface::GENERATED_ALT_TEXTS, $altTexts);
        $this->setGeneratedAltTextCount($altTexts);

        return $this;
    }

    /**
     * @throws LocalizedException
     */
    public function addGeneratedAltText(string $imageSrc, string $altText): StatusInterface
    {
        if (empty($imageSrc) || empty($altText)) {
            throw new LocalizedException(__('Image src and alt text are required'));
        }
        if (self::SIMPLE_SYMBOL_INDICATION === $altText) {
            // A bare prefix has no description after it, so it is not stored.
            return $this;
        }

        $generatedAltTextModel = $this->generatedAltTextFactory->create()
            ->setImageSrc($imageSrc)
            ->setAltText($altText);

        // Strip the prefix and mark the text reusable for any record that uses the same image.
        if (str_starts_with($altText, self::SIMPLE_SYMBOL_INDICATION)) {
            $generatedAltTextModel->setAltText(trim(str_replace(self::SIMPLE_SYMBOL_INDICATION, '', $altText)));
            $generatedAltTextModel->setIsGloballyAvailable(true);
        }

        $this->setGeneratedAltTexts(
            array_merge(
                $this->getGeneratedAltTexts(),
                [$generatedAltTextModel->getImageSrc() => $generatedAltTextModel]
            )
        );

        return $this;
    }

    private function saveGeneratedAltTexts(): StatusInterface
    {
        foreach ($this->getGeneratedAltTexts() as $generatedAltTextModel) {
            if (!$generatedAltTextModel->getId()) {
                $generatedAltTextModel->setParentId($this->getId());
                $this->generatedAltTextRepository->save($generatedAltTextModel);
            }
        }

        return $this;
    }

    public function setGeneratedAltTextCount(array $altTexts): StatusInterface
    {
        // Reusable texts are not counted against this record.
        $counter = 0;
        foreach ($altTexts as $nextAltText) {
            if (!$nextAltText->isGloballyAvailable()) {
                $counter++;
            }
        }
        $this->setData(StatusInterface::GENERATED_ALT_TEXT_COUNT, $counter);

        return $this;
    }

    public function getTargetId(): int
    {
        return $this->getData(StatusInterface::TARGET_ID) ?: 0;
    }

    public function setTargetId(int $targetId): StatusInterface
    {
        $this->setData(StatusInterface::TARGET_ID, $targetId);

        return $this;
    }

    public function getTargetType(): string
    {
        return $this->getData(StatusInterface::TARGET_TYPE) ?: '';
    }

    public function setTargetType(string $targetType): StatusInterface
    {
        $this->setData(StatusInterface::TARGET_TYPE, $targetType);

        return $this;
    }

    public function getStoreId(): int
    {
        return $this->getData(StatusInterface::STORE_ID) ?: 0;
    }

    public function setStoreId(int $storeId): StatusInterface
    {
        $this->setData(StatusInterface::STORE_ID, $storeId);

        return $this;
    }

    public function getMissingAltTextCount(): int
    {
        return $this->getData(StatusInterface::MISSING_ALT_TEXT_COUNT) ?: 0;
    }

    public function setMissingAltTextCount(int $count): StatusInterface
    {
        $this->setData(StatusInterface::MISSING_ALT_TEXT_COUNT, $count);

        return $this;
    }

    public function getGeneratedAltTextCount(): int
    {
        return $this->getData(StatusInterface::GENERATED_ALT_TEXT_COUNT) ?: 0;
    }

    public function getState(): string
    {
        return $this->getData(StatusInterface::STATE) ?: '';
    }

    public function setState(string $state): StatusInterface
    {
        $this->setData(StatusInterface::STATE, $state);

        return $this;
    }

    public function getSeoText(): string
    {
        return $this->getData(StatusInterface::SEO_TEXT) ?: '';
    }

    public function setSeoText(string $seoText): StatusInterface
    {
        $this->setData(StatusInterface::SEO_TEXT, $seoText);

        return $this;
    }

    public function setScanInfo(string $info): StatusInterface
    {
        $this->setData(StatusInterface::SCAN_INFO, $info);

        return $this;
    }

    public function addScanInfo(string $info): StatusInterface
    {
        $this->setData(
            StatusInterface::SCAN_INFO,
            $this->getScanInfo() . $info . PHP_EOL
        );

        return $this;
    }

    public function getScanInfo(): string
    {
        return $this->getData(StatusInterface::SCAN_INFO) ?: '';
    }

    public function setGenerationInfo(string $info): StatusInterface
    {
        $this->setData(StatusInterface::GENERATION_INFO, $info);

        return $this;
    }

    public function addGenerationInfo(string $info): StatusInterface
    {
        $this->setData(
            StatusInterface::GENERATION_INFO,
            $this->getGenerationInfo() . $info . PHP_EOL
        );

        return $this;
    }

    public function getGenerationInfo(): string
    {
        return $this->getData(StatusInterface::GENERATION_INFO) ?: '';
    }

    public function getImageToAltTextMap(): array
    {
        $result = [];

        $rawDataRecord = $this->auditAggregator->getSingleRawContentRecord($this);
        $pregForImgs = '/<img\b[^>]*>/i';
        preg_match_all($pregForImgs, $rawDataRecord['content'], $images);

        foreach ($images[0] as $nextImage) {
            $normalizedImageElement = str_replace('&quot;', '"', $nextImage);
            $normalizedImageElement = str_replace('{{media url="', '{{media url=', $normalizedImageElement);
            $normalizedImageElement = str_replace('"}}', '}}', $normalizedImageElement);
            preg_match('/<img\b[^>]*\bsrc\s*=\s*"([^"]*)"/i', $normalizedImageElement, $imageSrcs);
            $imageSrc = $imageSrcs[1] ?? '';
            $generatedAltText = $this->findGeneratedAltText($imageSrc);
            if (!empty($generatedAltText)) {
                $result[$imageSrc] = $generatedAltText;
            }
        }

        return $result;
    }

    public function isAltTextGenerationAllowed(): bool
    {
        return $this->getData(StatusInterface::ALT_TEXT_GENERATION_ALLOWED) ?: false;
    }

    public function setAltTextGenerationAllowed(bool $allowed): StatusInterface
    {
        $this->setData(StatusInterface::ALT_TEXT_GENERATION_ALLOWED, $allowed);

        return $this;
    }
}
