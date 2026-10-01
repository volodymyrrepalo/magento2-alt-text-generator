<?php

namespace MagePackHelper\AltTextGenerator\Controller\Adminhtml\Status;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use MagePackHelper\AltTextGenerator\Api\AuditAggregatorInterface;
use Exception;

class Scan extends Action
{
    public const string ADMIN_RESOURCE = 'MagePackHelper_AltTextGenerator::status';

    public function __construct(
        Context $context,
        private readonly AuditAggregatorInterface $auditAggregator,
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $recordId = (int)$this->getRequest()->getParam('id');
            $this->auditAggregator->scan($recordId);
            $this->messageManager->addSuccessMessage(
                __('Record number %1 was scanned.', $recordId)
            );
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->_redirect('*/*/index');
    }
}
