<?php

namespace MagePackHelper\AltTextGenerator\Controller\Adminhtml\Status;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use MagePackHelper\AltTextGenerator\Api\AuditAggregatorInterface;
use Exception;

class Save extends Action
{
    public function __construct(
        Context $context,
        private readonly AuditAggregatorInterface $auditAggregator,
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create()->setPath('*/*/');

        try {
            $data = $this->getRequest()->getPostValue();
            $content = $data['content'];
            if (empty($content)) {
                $this->messageManager->addErrorMessage(__('Cannot save empty content.'));

                return $resultRedirect;
            }
            $this->auditAggregator->updateContent($content, $data['entity_id']);
            $this->messageManager->addSuccessMessage(
                __('Content for record number %1 was saved.', $data['entity_id'])
            );
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect;
    }
}
