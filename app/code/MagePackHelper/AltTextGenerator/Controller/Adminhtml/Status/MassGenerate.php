<?php

namespace MagePackHelper\AltTextGenerator\Controller\Adminhtml\Status;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use MagePackHelper\AltTextGenerator\Api\AuditAggregatorInterface;
use Exception;

class MassGenerate extends Action
{
    public const string ADMIN_RESOURCE = 'MagePackHelper_AltTextGenerator::status';

    public function __construct(
        Context $context,
        private readonly AuditAggregatorInterface $auditAggregator,
    ) {
        parent::__construct($context);
    }

    public function execute(): void
    {
        $recordIds = $this->getRequest()->getParam('selected');
        foreach ($recordIds as $nextId) {
            try {
                $this->auditAggregator->generate((int)$nextId);
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }

        if (count($recordIds)) {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) were alt text generated.', count($recordIds))
            );
        }

        $this->_redirect('*/*/index');
    }
}
