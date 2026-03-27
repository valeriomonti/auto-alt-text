<?php

declare(strict_types=1);

namespace AATXT\App\Frontend;

/**
 * Filters post content to inject alt text from the Media Library into <img> tags.
 *
 * This filter runs at priority 999 on `the_content` to ensure page builders
 * (Elementor, Divi, etc.) have already processed their shortcodes.
 *
 * Attachment ID resolution uses three strategies in order:
 * 1. CSS class `wp-image-{ID}` (Gutenberg, Classic Editor, most page builders)
 * 2. Attribute `data-id="{ID}"` (Elementor)
 * 3. Reverse lookup from `src` URL via `attachment_url_to_postid()` (universal fallback)
 */
final class ContentAltTextFilter
{
    /**
     * Whether the frontend alt text injection is enabled.
     *
     * @var bool
     */
    private $enabled;

    /**
     * Whether to overwrite existing alt text in content.
     *
     * @var bool
     */
    private $overwriteExisting;

    /**
     * In-memory cache: attachment ID => alt text.
     *
     * @var array<int, string>
     */
    private $altTextCache = [];

    /**
     * In-memory cache: image URL => attachment ID.
     *
     * @var array<string, int>
     */
    private $urlToIdCache = [];

    /**
     * @param bool $enabled Whether the filter is active
     * @param bool $overwriteExisting Whether to overwrite non-empty alt text in content
     */
    public function __construct(bool $enabled, bool $overwriteExisting)
    {
        $this->enabled = $enabled;
        $this->overwriteExisting = $overwriteExisting;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Filter post content to inject alt text into <img> tags.
     *
     * @param string $content The post content
     * @return string The filtered content
     */
    public function filter(string $content): string
    {
        if ($content === '' || stripos($content, '<img') === false) {
            return $content;
        }

        return (string) preg_replace_callback(
            '/<img\b([^>]*)>/i',
            [$this, 'processImgTag'],
            $content
        );
    }

    /**
     * Process a single <img> tag match.
     *
     * @param array<int, string> $matches Regex matches
     * @return string The processed <img> tag
     */
    private function processImgTag(array $matches): string
    {

        $fullTag = $matches[0];
        $attributes = $matches[1];

        // Check existing alt attribute
        if (preg_match('/\balt\s*=\s*(["\'])(.*?)\1/i', $attributes, $altMatch)) {
            $existingAlt = $altMatch[2];

            // Non-empty alt: keep it unless overwrite is on
            if ($existingAlt !== '' && !$this->overwriteExisting) {
                return $fullTag;
            }
            // If overwrite is on but attachment has no alt text, keep original
        }

        $attachmentId = $this->extractAttachmentId($attributes);

        if ($attachmentId === null) {
            return $fullTag;
        }

        $altText = $this->getAltTextForAttachment($attachmentId);
        if ($altText === '') {
            return $fullTag;
        }

        return $this->injectAltText($fullTag, $attributes, $altText);
    }

    /**
     * Try to extract an attachment ID from <img> tag attributes.
     *
     * Tries three strategies in order:
     * 1. wp-image-{ID} CSS class
     * 2. data-id attribute (Elementor)
     * 3. Reverse lookup from src URL
     *
     * @param string $attributes The attributes string from the <img> tag
     * @return int|null The attachment ID, or null if not found
     */
    private function extractAttachmentId(string $attributes): ?int
    {
        $id = $this->getAttachmentIdFromClass($attributes);
        if ($id !== null) {
            return $id;
        }

        $id = $this->getAttachmentIdFromDataId($attributes);
        if ($id !== null) {
            return $id;
        }

        return $this->getAttachmentIdFromSrc($attributes);
    }

    /**
     * Extract attachment ID from wp-image-{ID} CSS class.
     *
     * @param string $attributes The attributes string
     * @return int|null
     */
    private function getAttachmentIdFromClass(string $attributes): ?int
    {
        if (preg_match('/\bwp-image-(\d+)\b/i', $attributes, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Extract attachment ID from data-id attribute (Elementor).
     *
     * @param string $attributes The attributes string
     * @return int|null
     */
    private function getAttachmentIdFromDataId(string $attributes): ?int
    {
        if (preg_match('/\bdata-id\s*=\s*["\'](\d+)["\']/i', $attributes, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Extract attachment ID by reverse-looking up the src URL.
     *
     * This is the most expensive strategy (DB query) and is used as last resort.
     * Results are cached in memory for the duration of the request.
     *
     * @param string $attributes The attributes string
     * @return int|null
     */
    private function getAttachmentIdFromSrc(string $attributes): ?int
    {
        if (!preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $attributes, $matches)) {
            return null;
        }

        $url = $matches[1];

        if (isset($this->urlToIdCache[$url])) {
            $id = $this->urlToIdCache[$url];
            return $id > 0 ? $id : null;
        }

        $id = (int) attachment_url_to_postid($url);
        $this->urlToIdCache[$url] = $id;

        return $id > 0 ? $id : null;
    }

    /**
     * Get alt text for an attachment from post meta, with caching.
     *
     * @param int $attachmentId The attachment ID
     * @return string The alt text, or empty string if none
     */
    private function getAltTextForAttachment(int $attachmentId): string
    {
        if (isset($this->altTextCache[$attachmentId])) {
            return $this->altTextCache[$attachmentId];
        }

        $altText = (string) get_post_meta($attachmentId, '_wp_attachment_image_alt', true);
        $this->altTextCache[$attachmentId] = $altText;

        return $altText;
    }

    /**
     * Inject or replace the alt attribute in an <img> tag.
     *
     * @param string $imgTag The full <img> tag
     * @param string $attributes The attributes string
     * @param string $altText The alt text to inject
     * @return string The modified <img> tag
     */
    private function injectAltText(string $imgTag, string $attributes, string $altText): string
    {
        $escapedAlt = esc_attr($altText);

        // Replace existing alt attribute
        if (preg_match('/\balt\s*=\s*(["\'])(.*?)\1/i', $attributes)) {
            return (string) preg_replace(
                '/\balt\s*=\s*(["\'])(.*?)\1/i',
                'alt="' . $escapedAlt . '"',
                $imgTag,
                1
            );
        }

        // Add alt attribute before the closing >
        return (string) preg_replace(
            '/\s*\/?>$/i',
            ' alt="' . $escapedAlt . '"$0',
            $imgTag,
            1
        );
    }
}
