<?php

namespace MagePackHelper\AltTextGenerator\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use MagePackHelper\AltTextGenerator\Api\AuditAggregatorInterface;
use MagePackHelper\AltTextGenerator\Api\Data\StatusInterface;
use MagePackHelper\AltTextGenerator\Api\StatusRepositoryInterface;
use MagePackHelper\AltTextGenerator\Api\DataAccessorInterface;

/**
 * Runs scan, generate, and fix across product descriptions, CMS pages, and CMS blocks.
 */
class AuditAggregator implements AuditAggregatorInterface
{
    public function __construct(
        private readonly StatusRepositoryInterface $statusRepository,
        private readonly AuditHandler $auditHandler,
        private readonly array $dataAccessors = [],
    ) {
    }

    /**
     * @throws NotFoundException
     * @throws LocalizedException
     */
    public function scan(int $statusId = 0): void
    {
        // Zero means every configured content source. A positive id rescans one record.
        if ($statusId > 0) {
            $status = $this->statusRepository->getById($statusId);
            $dataAccessor = $this->getDataAccessor($status->getTargetType());
            $this->scanAccessorData($dataAccessor, $status);

            return;
        }

        /** @var DataAccessorInterface $dataAccessor */
        foreach ($this->dataAccessors as $dataAccessor) {
            $this->scanAccessorData($dataAccessor);
        }
    }

    private function scanAccessorData(DataAccessorInterface $dataAccessor, ?StatusInterface $status = null): void
    {
        $rawDataRecords = $dataAccessor->getRawContentRecords($status);
        foreach ($rawDataRecords as $nextRawDataRecord) {
            $status = $this->statusRepository->getByTargetData(
                $dataAccessor->getTargetType(),
                $nextRawDataRecord['owner_id'],
                $nextRawDataRecord['store_id']
            );
            $this->auditHandler->scan($nextRawDataRecord['content'], $status);

            $this->perpetuateStatus($status);
        }
    }

    /**
     * @throws NotFoundException
     * @throws LocalizedException
     */
    public function generate(int $statusId): void
    {
        // Stores the generated texts on the status row. Writing the HTML back is done by fix().
        $status = $this->statusRepository->getById($statusId);
        try {
            $rawDataRecord = $this->getSingleRawContentRecord($status);
            $this->auditHandler->generate($rawDataRecord['content'], $status);
        } catch (LocalizedException $e) {
            $status->addGenerationInfo($e->getMessage());
        }

        $this->perpetuateStatus($status);

        // Re scan the content to refresh status information.
        $this->scan($statusId);
    }

    /**
     * @throws NotFoundException
     * @throws LocalizedException
     */
    public function fix(int $statusId): void
    {
        $status = $this->statusRepository->getById($statusId);
        $dataAccessor = null;
        try {
            $rawDataRecord = $this->getSingleRawContentRecord($status);
            $fixedContent = $this->auditHandler->fix($rawDataRecord['content'], $status);
            if (empty($fixedContent)) {
                return;
            }
            $dataAccessor = $this->getDataAccessor($status->getTargetType());
            $dataAccessor->saveContent($fixedContent, $status);
            $status->addGenerationInfo("Generated alt texts have been saved to the database.");
        } catch (LocalizedException $e) {
            $status->addGenerationInfo($e->getMessage());
        }

        $this->perpetuateStatus($status);

        // Re scan the content to refresh status information.
        $this->scan($statusId);
    }

    /**
     * @throws NotFoundException
     * @throws LocalizedException
     */
    public function updateContent(string $content, int $statusId): void
    {
        $status = $this->statusRepository->getById($statusId);
        $dataAccessor = $this->getDataAccessor($status->getTargetType());
        $dataAccessor->saveContent($content, $status);
    }

    /**
     * @throws LocalizedException
     */
    public function getSingleRawContentRecord(StatusInterface $status): array
    {
        $dataAccessor = $this->getDataAccessor($status->getTargetType());
        $records = $dataAccessor->getRawContentRecords($status);
        if (count($records) != 1) {
            throw new LocalizedException(__(
                'Wrong number of raw content records found for status model with ID = %1',
                $status->getId(),
            ));
        }

        return $records[0];
    }

    private function perpetuateStatus(StatusInterface $status): void
    {
        // A clean record is removed so the grid only lists content that still has gaps.
        if ($status->getMissingAltTextCount() == 0) {
            if ($status->getId()) {
                $this->statusRepository->delete((int)$status->getId());
            }
            return;
        }
        $this->statusRepository->save($status);
    }

    /**
     * @throws LocalizedException
     */
    private function getDataAccessor(string $targetType): ?DataAccessorInterface
    {
        $result = current(array_filter(
            $this->dataAccessors,
            fn ($dataAccessor) => $dataAccessor->getTargetType() === $targetType
        ));

        if (empty($result)) {
            throw new LocalizedException(__(
                'Unable to find the data accessor for the %1 target type.',
                $targetType,
            ));
        }

        return $result;
    }
}
