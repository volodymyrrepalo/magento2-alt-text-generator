<?php

namespace MagePackHelper\AltTextGenerator\Service;

use Magento\Framework\App\Filesystem\DirectoryList;
use MagePackHelper\AltTextGenerator\Api\Data\StatusInterface;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\Filesystem;
use DOMDocument;
use Exception;

/**
 * Finds images without alt text and, when generation is allowed, fills them in.
 */
readonly class HtmlProcessor
{
    public function __construct(
        private FilterProvider $filterProvider,
        private Filesystem $filesystem,
        private ChatGptAltTextProvider $chatGptAltTextProvider,
    ) {
    }

    public function execute(string $html, StatusInterface $status): string
    {
        $this->resetStatusStats($status);
        if (empty($status->getSeoText())) {
            $status->setSeoText($this->obtainSeoText($html));
        }

        return $this->processContentWithImages($html, $status);
    }

    private function resetStatusStats(StatusInterface $status): void
    {
        $status->setMissingAltTextCount(0);
        $status->setScanInfo('');
        if ($status->isAltTextGenerationAllowed()) {
            $status->setGenerationInfo('');
        }
    }

    private function obtainSeoText(string $html): string
    {
        // A space before each tag keeps neighbouring text from being glued together by libxml.
        $html = str_replace('<', ' <', $html);
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        libxml_clear_errors();

        $headers = $dom->getElementsByTagName("h1");
        if ($headers->length <= 0) {
            $headers = $dom->getElementsByTagName("h2");
            if ($headers->length <= 0) {
                $headers = $dom->getElementsByTagName("h3");
                if ($headers->length <= 0) {
                    return '';
                }
            }
        }

        // The first heading is the SEO context sent to ChatGPT together with the image.
        $seoText = $headers->item(0)->nodeValue;
        $seoText = strip_tags($seoText);
        $seoText = preg_replace("/[^a-zA-Z0-9,.+\-*&%\$]/", " ", $seoText);
        $seoText = preg_replace('/\s+/', ' ', $seoText);

        return "SEO context:\n" . strtolower(trim($seoText, " .\n\r\t\v\0")) . '.';
    }

    private function processContentWithImages(string $html, StatusInterface $status): string
    {
        $html = $this->processListItems($html, $status);

        // <img> tags that do not already have a non-empty quoted alt attribute.
        $pregForAltlessImgs = '/<img\b(?:(?!\balt\s*=\s*([\'"])[^\'"]+\1)[^>])*>/i';
        preg_match_all($pregForAltlessImgs, $html, $altlessImgs);

        foreach ($altlessImgs[0] as $nextAltlessImage) {
            $isAltEmpty = preg_match('/\balt\s*=\s*""/i', $nextAltlessImage);
            if (!$isAltEmpty && preg_match('/\balt\s*=\s*["\']/i', $nextAltlessImage)) {
                // The pattern can still match a tag that has alt in another form. Leave those unchanged.
                continue;
            }

            $status->setMissingAltTextCount($status->getMissingAltTextCount() + 1);

            $altText = $this->obtainAltText($nextAltlessImage, $status);
            if (empty($altText)) {
                continue;
            }

            $improvedImageElement = str_replace('alt=""', 'alt="' . $altText . '"', $nextAltlessImage);
            if (!$isAltEmpty) {
                $improvedImageElement = str_replace('>', ' alt="' . $altText . '">', $nextAltlessImage);
            }
            $html = str_replace($nextAltlessImage, $improvedImageElement, $html);
        }

        return $html;
    }

    private function processListItems(string $html, StatusInterface $status): string
    {
        // Page Builder stores the description in image_alt on the list item, not only in the <img> alt.
        preg_match_all('/<li\b[^>]*\bdata-content-type="list-item".*?<\/li>/i', $html, $liElements);
        foreach ($liElements[0] as $nextLiElement) {
            $isImageAltEmpty = preg_match('/\bimage_alt\s*=\s*""/i', $nextLiElement);
            $isImageAltMissing = !str_contains($nextLiElement, 'image_alt');

            // <img ... data-element="image"...>
            preg_match('/<img\b[^>]*\bdata-element="image"[^>]*>/i', $nextLiElement, $liImages);
            $oneAndOnlyLiImage = $liImages[0] ?? '';
            if (empty($oneAndOnlyLiImage)) {
                // $status->addScanInfo("No image found in the list item: " . $nextLiElement);
                continue;
            }
            if ($isImageAltEmpty || $isImageAltMissing) {
                $status->setMissingAltTextCount($status->getMissingAltTextCount() + 1);

                $newAltText = $this->obtainAltText($oneAndOnlyLiImage, $status);
                if (empty($newAltText)) {
                    continue;
                }

                // Change the <li> element.
                $newLiElement = str_replace('image_alt=""', 'image_alt="' . $newAltText . '"', $nextLiElement);
                if ($isImageAltMissing) {
                    $newLiElement = str_replace(
                        'data-content-type="list-item"',
                        'data-content-type="list-item" image_alt="' . $newAltText . '"',
                        $nextLiElement
                    );
                }

                // Change the <img element.
                if (!str_contains($oneAndOnlyLiImage, 'alt="')) {
                    $newOneAndOnlyLiImage = str_replace(
                        'data-element="image"',
                        'data-element="image" alt="' . $newAltText . '"',
                        $oneAndOnlyLiImage
                    );
                    $newLiElement = str_replace($oneAndOnlyLiImage, $newOneAndOnlyLiImage, $newLiElement);
                }

                $html = str_replace($nextLiElement, $newLiElement, $html);
            }
        }

        return $html;
    }

    /**
     * @throws Exception
     */
    private function obtainAltText(string $imageElement, StatusInterface $status): string
    {
        // Page Builder writes the media directive as {{media url="..."}}. Flatten the quotes so src can be read.
        $normalizedImageElement = str_replace('&quot;', '"', $imageElement);
        $normalizedImageElement = str_replace('{{media url="', '{{media url=', $normalizedImageElement);
        $normalizedImageElement = str_replace('"}}', '}}', $normalizedImageElement);
        preg_match('/<img\b[^>]*\bsrc\s*=\s*"([^"]*)"/i', $normalizedImageElement, $imageSrcs);
        $imageSrc = $imageSrcs[1] ?? '';
        if (empty($imageSrc)) {
            $status->addScanInfo("img element have no src attribute: " . $imageElement);

            return '';
        }
        if (str_starts_with($imageSrc, 'data:')) {
            $abridgedImageElement = str_replace($imageSrc, 'data:......', $imageElement);
            $status->addScanInfo("Image src is inline data: " . $abridgedImageElement);

            return '';
        }
        // Skip images that are not a readable file in pub/media, so ChatGPT is not called for them.
        $imageFileName = $this->getImageFullFileName($imageSrc, $status);
        if (empty($imageFileName)) {
            $status->addScanInfo("Image file name is empty: " . $imageElement);

            return '';
        }
        if (!file_exists($imageFileName)) {
            $status->addScanInfo("Image file not found: " . $imageFileName);

            return '';
        }
        if (!is_readable($imageFileName)) {
            $status->addScanInfo("Image file is not readable: " . $imageFileName);

            return '';
        }

        $previouslyGeneratedAltText = $status->findGeneratedAltText($imageSrc);
        if (!empty($previouslyGeneratedAltText)) {
            return $previouslyGeneratedAltText;
        }

        // A scan only counts the gap. ChatGPT runs when the caller has allowed generation.
        if (!$status->isAltTextGenerationAllowed()) {
            return '';
        }
        $freshlyGeneratedAltText = $this->chatGptAltTextProvider->execute(
            $this->filterProvider->getPageFilter()->filter($imageSrc),
            $status
        );
        if (empty($freshlyGeneratedAltText)) {
            return '';
        }

        $status->addGeneratedAltText($imageSrc, $freshlyGeneratedAltText);

        return $status->findGeneratedAltText($imageSrc);
    }

    private function getImageFullFileName(string $imageSrc, StatusInterface $status): string
    {
        preg_match('/\{\{media\s+url=([^\}]+)\}\}/', $imageSrc, $matches);
        $urlValue = trim($matches[1] ?? '', " '\"");
        if (empty($urlValue)) {
            $status->addScanInfo("URL value can not be found for this 'src': " . $imageSrc);

            return '';
        }
        $mediaDir = $this->filesystem
            ->getDirectoryRead(DirectoryList::MEDIA)
            ->getAbsolutePath();

        return $mediaDir . ltrim($urlValue, '/');
    }
}
