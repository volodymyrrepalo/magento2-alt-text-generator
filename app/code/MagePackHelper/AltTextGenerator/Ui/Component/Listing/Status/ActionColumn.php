<?php

namespace MagePackHelper\AltTextGenerator\Ui\Component\Listing\Status;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class ActionColumn extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['entity_id'])) {
                    $scanUrlPath = $this->getData('config/scanUrlPath') ?: '#';
                    $generateUrlPath = $this->getData('config/generateUrlPath') ?: '#';
                    $fixUrlPath = $this->getData('config/fixUrlPath') ?: '#';
                    $editUrlPath = $this->getData('config/editUrlPath') ?: '#';
                    $urlEntityParamName = $this->getData('config/urlEntityParamName') ?: 'entity_id';

                    $item[$this->getData('name')] = [
                        'edit' => [
                            'href' => $this->urlBuilder->getUrl(
                                $editUrlPath,
                                [
                                    $urlEntityParamName => $item['entity_id']
                                ]
                            ),
                            'label' => __('Details'),
                            'isAjax' => true,
                        ],
                        'scan' => [
                            'href' => $this->urlBuilder->getUrl(
                                $scanUrlPath,
                                [
                                    $urlEntityParamName => $item['entity_id']
                                ]
                            ),
                            'label' => __('Scan')
                        ],
                        'generate' => [
                            'href' => $this->urlBuilder->getUrl(
                                $generateUrlPath,
                                [
                                    $urlEntityParamName => $item['entity_id']
                                ]
                            ),
                            'label' => __('Generate')
                        ],
                        'fix' => [
                            'href' => $this->urlBuilder->getUrl(
                                $fixUrlPath,
                                [
                                    $urlEntityParamName => $item['entity_id']
                                ]
                            ),
                            'label' => __('Fix'),
                            'confirm' => [
                                'title' => __('Fix alt text'),
                                'message' => __('Are you sure you want to fix alt texts for this entity?')
                            ]
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }
}
