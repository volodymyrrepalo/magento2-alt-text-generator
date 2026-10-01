<?php

namespace MagePackHelper\AltTextGenerator\Cron;

use MagePackHelper\AltTextGenerator\Api\AuditAggregatorInterface;

/**
 * Nightly scan. It records missing alt text and does not change content.
 */
readonly class CheckAltText
{
    public function __construct(
        private AuditAggregatorInterface $auditAggregator,
    ) {
    }

    public function run(): void
    {
        $this->auditAggregator->scan();
    }
}
