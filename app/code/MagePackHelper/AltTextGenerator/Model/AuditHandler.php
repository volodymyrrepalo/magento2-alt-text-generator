<?php

namespace MagePackHelper\AltTextGenerator\Model;

use Magento\Framework\Exception\LocalizedException;
use MagePackHelper\AltTextGenerator\Api\Data\StatusInterface;
use MagePackHelper\AltTextGenerator\Service\HtmlProcessor;

class AuditHandler
{
    public function __construct(
        private readonly HtmlProcessor $htmlProcessor,
    ) {
    }

    public function scan(string $html, StatusInterface $status): void
    {
        $status->setAltTextGenerationAllowed(false);    // Disable alt text generation in htmlProcessor.
        $this->htmlProcessor->execute($html, $status);
        $status->addScanInfo("Scan completed at " . date("Y-m-d H:i:s"));
    }

    /**
     * @throws LocalizedException
     */
    public function generate(string $html, StatusInterface $status): string
    {
        if (!$status->getId()) {
            throw new LocalizedException(__('Status object should be saved before generating alt text'));
        }
        $status->setAltTextGenerationAllowed(true);    // Enable alt text generation in htmlProcessor.
        $html = $this->htmlProcessor->execute($html, $status);
        $status->addGenerationInfo("Generation completed at " . date("Y-m-d H:i:s"));

        return $html;
    }

    /**
     * @throws LocalizedException
     */
    public function fix(string $html, StatusInterface $status): string
    {
        $originalHtml = $html;
        $html = $this->generate($html, $status);

        if ($html === $originalHtml) {
            $status->addGenerationInfo("NO CHANGES WERE MADE TO THE DATABASE.");
            return '';
        }

        return $html;
    }
}
