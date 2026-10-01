<?php

namespace MagePackHelper\AltTextGenerator\Component;

use Magento\Ui\Component\Container;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\App\RequestInterface;
use MagePackHelper\AltTextGenerator\Api\StatusRepositoryInterface;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\Exception\NotFoundException;
use Exception;

class GeneratedAltTexts extends Container
{
    public function __construct(
        ContextInterface $context,
        private readonly RequestInterface $request,
        private readonly StatusRepositoryInterface $statusRepository,
        private readonly FilterProvider $filterProvider,
        array $components = [],
        array $data = []
    ) {
        parent::__construct(
            $context,
            $components,
            $data
        );
    }

    /**
     * @throws NotFoundException
     * @throws Exception
     */
    public function prepare(): void
    {
        parent::prepare();

        $statusId = (int)$this->request->getParam('id');
        $status = $this->statusRepository->getById($statusId);

        $imageToAltTextMap = $status->getImageToAltTextMap();
        if (empty($imageToAltTextMap)) {
            return;
        }

        $resultHtml = null;
        foreach ($imageToAltTextMap as $imageSrc => $altText) {
            $resultHtml = $resultHtml ?? '<h1 style="margin: 33px 0;">Generated Alt Texts</h1><ul>';

            $listItemHtml = '<div class="item"><img src="' . $imageSrc . '"/></div>';
            $listItemHtml = $this->filterProvider->getPageFilter()->filter($listItemHtml);
            // The admin preview must load the image from the public CDN, not from a local or admin host.
            $listItemHtml = str_replace('http://loc1.dev.approvedscience.com', 'https://i.approvedscience.com', $listItemHtml);
            $listItemHtml = str_replace('https://loc1.dev.approvedscience.com', 'https://i.approvedscience.com', $listItemHtml);
            $listItemHtml = str_replace('https://app.idingo.com', 'https://i.approvedscience.com', $listItemHtml);

            $resultHtml .= "<li style=\"display: flex; align-items: center;\">$listItemHtml<span style=\"margin-left: 33px;\">$altText</span></li>";
        }

        $resultHtml .= '<ul>';

        $config = $this->getData('config');
        $config['content'] = $resultHtml;
        $this->setData('config', (array) $config);
    }
}
