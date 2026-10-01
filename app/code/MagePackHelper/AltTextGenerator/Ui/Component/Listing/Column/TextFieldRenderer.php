<?php

namespace MagePackHelper\AltTextGenerator\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class TextFieldRenderer extends Column
{
    public function __construct(
        private readonly Escaper $escaper,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$fieldName] = $this->getValueHtml($item, $fieldName);
            }
        }
        return $dataSource;
    }

    private function getValueHtml(array $item, string $fieldName): string
    {
        return
            sprintf(
                '<div class="%s values-comparison %s">%s</div>',
                $fieldName,
                // Highlight the cell while this record still has images without alt text.
                ((int)$item['missing_alt_text_count'] > 0) ? 'not-in-sync' : '',
                $this->getElementHtml($item, $fieldName)
            );
    }

    private function getElementHtml(array $item, string $fieldName): string
    {
        $elementValue = $item[$fieldName] ?? '';
        $elementValue = $this->escaper->escapeHtml($elementValue);
        $elementValue = nl2br($elementValue);
        $elementValue = str_replace('  ', '&nbsp;', $elementValue);

        return empty($elementValue) ? '' :
            '<div class="' . $fieldName . '">'
            . $elementValue
            . '</div>';
    }
}
