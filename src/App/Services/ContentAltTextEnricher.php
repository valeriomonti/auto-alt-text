<?php

namespace AATXT\App\Services;

/**
 * Enriches rendered post content by filling in missing alt text on <img> tags.
 *
 * Hooked to the `the_content` filter, this service scans every image in the
 * content and, when an image has no alt text, tries to resolve the matching
 * attachment in the media library. If that attachment has an alt text stored
 * (`_wp_attachment_image_alt`), it is written back into the tag.
 *
 * Resolution strategy, in order of reliability:
 *  1. The `wp-image-<ID>` class that both the classic editor and the Gutenberg
 *     image block add to inserted images.
 *  2. The `src` URL, matched against the media library via
 *     {@see attachment_url_to_postid()}, also stripping any `-WxH` size suffix
 *     so that resized/cropped variants resolve to the original attachment.
 *
 * Parsing and rewriting is done with {@see \WP_HTML_Tag_Processor} (WordPress
 * >= 6.2), which is HTML5-spec compliant and escapes attribute values safely.
 *
 * The service never overwrites an image that already has a non-empty alt text.
 */
final class ContentAltTextEnricher
{
    /**
     * Per-request cache of URL => attachment ID lookups.
     *
     * @var array<string, int>
     */
    private $urlToIdCache = [];

    /**
     * Per-request cache of attachment ID => stored alt text.
     *
     * @var array<int, string>
     */
    private $altTextCache = [];

    /**
     * Fill missing alt text on every image found in the content.
     *
     * @param string $content The post content HTML
     * @return string The content with alt text filled where possible
     */
    public function enrich(string $content): string
    {
        // Cheap early exits before instantiating the parser.
        if ($content === '' || stripos($content, '<img') === false) {
            return $content;
        }

        // Defensive guard: the plugin requires WordPress >= 6.2, but a site that
        // ignored the requirement would not ship WP_HTML_Tag_Processor.
        if (! class_exists('WP_HTML_Tag_Processor')) {
            return $content;
        }

        /**
         * Allow disabling content enrichment entirely (e.g. per request/context).
         *
         * @param bool $enabled Whether to process the content. Default true.
         */
        if (! apply_filters('aatxt_content_alt_text_enabled', true)) {
            return $content;
        }

        $processor = new \WP_HTML_Tag_Processor($content);
        $modified = false;

        while ($processor->next_tag(['tag_name' => 'IMG'])) {
            $currentAlt = $processor->get_attribute('alt');

            // Skip images that already carry a real alt text. An absent attribute
            // (null) or an empty/whitespace value is treated as "missing".
            if (is_string($currentAlt) && trim($currentAlt) !== '') {
                continue;
            }

            $attachmentId = $this->resolveAttachmentId($processor);
            if ($attachmentId === 0) {
                continue;
            }

            $altText = $this->altTextForAttachment($attachmentId);
            if ($altText === '') {
                continue;
            }

            $processor->set_attribute('alt', $altText);
            $modified = true;
        }

        return $modified ? $processor->get_updated_html() : $content;
    }

    /**
     * Resolve the attachment ID for the image the processor is currently on.
     *
     * @param \WP_HTML_Tag_Processor $processor The processor positioned on an <img> tag
     * @return int The attachment ID, or 0 if it could not be resolved
     */
    private function resolveAttachmentId(\WP_HTML_Tag_Processor $processor): int
    {
        $class = $processor->get_attribute('class');
        $attachmentId = $this->attachmentIdFromClass(is_string($class) ? $class : '');

        if ($attachmentId === 0) {
            $src = $processor->get_attribute('src');
            if (is_string($src) && $src !== '') {
                $attachmentId = $this->attachmentIdFromUrl($src);
            }
        }

        return $attachmentId;
    }

    /**
     * Extract the attachment ID from a `wp-image-<ID>` class token.
     *
     * @param string $class The value of the image `class` attribute
     * @return int The attachment ID, or 0 if none is present
     */
    private function attachmentIdFromClass(string $class): int
    {
        if ($class !== '' && preg_match('/(?:^|\s)wp-image-(\d+)(?:\s|$)/', $class, $matches) === 1) {
            return (int) $matches[1];
        }

        return 0;
    }

    /**
     * Resolve an attachment ID from an image URL.
     *
     * Falls back to stripping a `-WxH` size suffix (e.g. `photo-300x200.jpg`)
     * so that intermediate image sizes resolve to their original attachment.
     *
     * @param string $url The image `src` URL
     * @return int The attachment ID, or 0 if it could not be resolved
     */
    private function attachmentIdFromUrl(string $url): int
    {
        if (array_key_exists($url, $this->urlToIdCache)) {
            return $this->urlToIdCache[$url];
        }

        $attachmentId = (int) attachment_url_to_postid($url);

        if ($attachmentId === 0) {
            $fullSizeUrl = preg_replace('/-\d+x\d+(\.[A-Za-z0-9]+)$/', '$1', $url);
            if (is_string($fullSizeUrl) && $fullSizeUrl !== $url) {
                $attachmentId = (int) attachment_url_to_postid($fullSizeUrl);
            }
        }

        $this->urlToIdCache[$url] = $attachmentId;

        return $attachmentId;
    }

    /**
     * Get the stored alt text for an attachment, cached per request.
     *
     * @param int $attachmentId The attachment ID
     * @return string The stored alt text, or empty string if none is set
     */
    private function altTextForAttachment(int $attachmentId): string
    {
        if (array_key_exists($attachmentId, $this->altTextCache)) {
            return $this->altTextCache[$attachmentId];
        }

        $storedAlt = get_post_meta($attachmentId, '_wp_attachment_image_alt', true);
        $altText = is_string($storedAlt) ? trim($storedAlt) : '';

        /**
         * Filter the alt text resolved from the media library before it is
         * written into the content image tag.
         *
         * @param string $altText      The resolved alt text.
         * @param int    $attachmentId The attachment ID it was resolved from.
         */
        $altText = (string) apply_filters('aatxt_content_image_alt_text', $altText, $attachmentId);

        $this->altTextCache[$attachmentId] = $altText;

        return $altText;
    }
}
