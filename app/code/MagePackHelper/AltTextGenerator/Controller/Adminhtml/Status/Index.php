<?php

namespace MagePackHelper\AltTextGenerator\Controller\Adminhtml\Status;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;

class Index extends Action
{
    public const string ADMIN_RESOURCE = 'MagePackHelper_AltTextGenerator::status';

    public function execute()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->prepend(__('Missing Alt Texts'));

        return $resultPage;
    }
}
