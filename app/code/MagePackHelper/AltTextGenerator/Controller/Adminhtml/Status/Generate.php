<?php

namespace MagePackHelper\AltTextGenerator\Controller\Adminhtml\Status;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use MagePackHelper\AltTextGenerator\Api\AuditAggregatorInterface;
use Exception;

class Generate extends Action
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
            $this->auditAggregator->generate($recordId);
            $this->messageManager->addSuccessMessage(
                __('Alt texts for the record number %1 were generated.', $recordId)
            );
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->_redirect('*/*/index');
    }
}
