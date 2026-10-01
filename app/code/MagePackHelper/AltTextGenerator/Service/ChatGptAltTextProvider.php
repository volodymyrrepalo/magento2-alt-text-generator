<?php

namespace MagePackHelper\AltTextGenerator\Service;

use DateInterval;
use DateInvalidOperationException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use MagePackHelper\AltTextGenerator\Api\Data\StatusInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use MagePackHelper\AltTextGenerator\Model\GeneratedAltTextRepositoryInterface;
use Magento\Framework\Encryption\EncryptorInterface;

/**
 * Asks ChatGPT for alt text. Returns an empty string when the daily limit is hit or the API fails.
 */
class ChatGptAltTextProvider
{
    private const int ADMIN_WEBSITE_ID = 0;
    private const string XML_PATH_MAX_GENERATIONS_PER_DAY = 'alt_text/chat_gpt/max_generations_per_day';
    private const string XML_PATH_KEY = 'alt_text/chat_gpt/key';
    private const string XML_PATH_INSTRUCTIONS = 'alt_text/chat_gpt/instructions';

    private int $altTextsGeneratedToday = 0;

    public function __construct(
        private readonly GeneratedAltTextRepositoryInterface $generatedAltTextRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly TimezoneInterface $timezone,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor,
    ) {
    }

    /**
     * @throws DateInvalidOperationException
     */
    public function execute(string $imageUrl, StatusInterface $status): string
    {
        $this->altTextsGeneratedToday = $this->getAltTextsGeneratedToday();
        if ($this->altTextsGeneratedToday >= $this->getMaxGenerationsPerDay()) {
            $status->addGenerationInfo('The  daily  limit  for  ChatGPT  generations  has  been  reached.');

            return '';
        }
        $this->altTextsGeneratedToday++;

        $api_key = $this->getChatGptKey();

        // ChatGPT downloads the image itself, so local and admin hosts are rewritten to the public CDN.
        $imageUrl = str_replace('http://loc1.dev.approvedscience.com', 'https://i.approvedscience.com', $imageUrl);
        $imageUrl = str_replace('https://loc1.dev.approvedscience.com', 'https://i.approvedscience.com', $imageUrl);
        $imageUrl = str_replace('https://app.idingo.com', 'https://i.approvedscience.com', $imageUrl);

        $instructionsAndSeoText = $this->getInstructions() . PHP_EOL . $status->getSeoText();
        $data = [
            "model" => "gpt-4.1",
            "input" => [
                [
                    "role" => "user",
                    "content" => [
                        ["type" => "input_text", "text" => $instructionsAndSeoText],
                        ["type" => "input_image", "image_url" => $imageUrl]
                    ]
                ]
            ]
        ];

        $ch = curl_init("https://api.openai.com/v1/responses");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $api_key
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        if ($response === false) {
            $status->addGenerationInfo("cURL Error: " . curl_error($ch));
            curl_close($ch);
            return '';
        }
        curl_close($ch);

        $decodedResponse = json_decode($response, true);

        $result = $decodedResponse['output'][0]['content'][0]['text'] ?? '';

        if (empty($result)) {
            $status->addGenerationInfo('No generated alt text found in this response: ' . $response);
        }

        return $result;
    }

    private function getChatGptKey(): string
    {
        return $this->encryptor->decrypt(
            $this->scopeConfig->getValue(
                self::XML_PATH_KEY,
                ScopeInterface::SCOPE_WEBSITE,
                self::ADMIN_WEBSITE_ID
            )
        );
    }

    /**
     * @throws DateInvalidOperationException
     */
    private function getAltTextsGeneratedToday(): int
    {
        if (empty($this->altTextsGeneratedToday)) {
            // "Today" is a rolling 24-hour window, counted from rows updated since this time yesterday.
            $yesterday = $this->timezone->date()
                ->sub(new DateInterval('P1D'))
                ->format('Y-m-d H:i:s');

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('updated_at', $yesterday, 'gteq')
                ->create();

            $this->altTextsGeneratedToday =
                count($this->generatedAltTextRepository->getList($searchCriteria)->getItems());
        }

        return $this->altTextsGeneratedToday;
    }

    private function getMaxGenerationsPerDay(): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_MAX_GENERATIONS_PER_DAY,
            ScopeInterface::SCOPE_WEBSITE,
            self::ADMIN_WEBSITE_ID
        );
    }

    private function getInstructions(): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_INSTRUCTIONS,
            ScopeInterface::SCOPE_WEBSITE,
            self::ADMIN_WEBSITE_ID
        );
    }
}
