<?php

namespace MagePackHelper\AltTextGenerator\Model;

use Exception;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NotFoundException;
use MagePackHelper\AltTextGenerator\Api\Data\StatusInterface;
use MagePackHelper\AltTextGenerator\Api\Data\StatusInterfaceFactory;
use MagePackHelper\AltTextGenerator\Api\StatusRepositoryInterface;
use MagePackHelper\AltTextGenerator\Model\ResourceModel\Status\CollectionFactory as StatusCollectionFactory;
use MagePackHelper\AltTextGenerator\Model\ResourceModel\Status as StatusResource;

class StatusRepository implements StatusRepositoryInterface
{

    public function __construct(
        private readonly StatusInterfaceFactory $statusFactory,
        private readonly StatusCollectionFactory $statusCollectionFactory,
        private readonly StatusResource $statusResource
    ) {
    }

    /**
     * @throws NotFoundException
     */
    public function getById(int $id): StatusInterface
    {
        $statusModel = $this->statusFactory->create();
        $this->statusResource->load($statusModel, $id);
        if (!$statusModel->getId()) {
            throw new NotFoundException(__('Status record with ID %1 does not exist', $id));
        }

        return $statusModel;
    }

    public function getByTargetData(string $targetType, int $targetId, int $storeId): StatusInterface
    {
        $collection =
            $this->statusCollectionFactory
                ->create()
                ->addFieldToFilter(StatusInterface::TARGET_TYPE, $targetType)
                ->addFieldToFilter(StatusInterface::TARGET_ID, $targetId)
                ->addFieldToFilter(StatusInterface::STORE_ID, $storeId);

        /** @var StatusInterface $statusModel */
        $statusModel = $collection->getFirstItem();
        if (!$statusModel->getId()) {
            $statusModel->setTargetType($targetType);
            $statusModel->setTargetId($targetId);
            $statusModel->setStoreId($storeId);
        }

        return $statusModel;
    }

    /**
     * @throws AlreadyExistsException
     */
    public function save(StatusInterface $status): StatusInterface
    {
        $this->statusResource->save($status);

        return $status;
    }

    /**
     * @throws NotFoundException
     * @throws Exception
     */
    public function delete(int $id): void
    {
        $model = $this->getById($id);
        $this->statusResource->delete($model);
    }
}
