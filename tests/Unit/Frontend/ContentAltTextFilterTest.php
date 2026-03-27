<?php

declare(strict_types=1);

/**
 * WordPress function stubs for the AATXT\App\Frontend namespace.
 *
 * These stubs are resolved first by PHP when unqualified function calls
 * are made from within the ContentAltTextFilter class.
 */
namespace AATXT\App\Frontend;

/**
 * Stub for get_post_meta.
 *
 * @param int $postId
 * @param string $key
 * @param bool $single
 * @return mixed
 */
function get_post_meta(int $postId, string $key = '', bool $single = false)
{
    global $aatxt_test_post_meta;
    if (isset($aatxt_test_post_meta[$postId][$key])) {
        return $aatxt_test_post_meta[$postId][$key];
    }
    return '';
}

/**
 * Stub for attachment_url_to_postid.
 *
 * @param string $url
 * @return int
 */
function attachment_url_to_postid(string $url): int
{
    global $aatxt_test_url_to_id;
    global $aatxt_test_url_lookup_count;
    if (!isset($aatxt_test_url_lookup_count)) {
        $aatxt_test_url_lookup_count = 0;
    }
    $aatxt_test_url_lookup_count++;
    if (isset($aatxt_test_url_to_id[$url])) {
        return $aatxt_test_url_to_id[$url];
    }
    return 0;
}

/**
 * Stub for esc_attr.
 *
 * @param string $text
 * @return string
 */
function esc_attr(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

namespace AATXT\Tests\Unit\Frontend;

use AATXT\App\Frontend\ContentAltTextFilter;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ContentAltTextFilter.
 */
class ContentAltTextFilterTest extends TestCase
{
    /** @var ContentAltTextFilter */
    private $filter;

    /** @var ContentAltTextFilter */
    private $filterWithOverwrite;

    protected function setUp(): void
    {
        global $aatxt_test_post_meta, $aatxt_test_url_to_id, $aatxt_test_url_lookup_count;
        $aatxt_test_post_meta = [];
        $aatxt_test_url_to_id = [];
        $aatxt_test_url_lookup_count = 0;

        $this->filter = new ContentAltTextFilter(true, false);
        $this->filterWithOverwrite = new ContentAltTextFilter(true, true);
    }

    public function testIsEnabledReturnsTrueWhenEnabled(): void
    {
        $filter = new ContentAltTextFilter(true, false);
        $this->assertTrue($filter->isEnabled());
    }

    public function testIsEnabledReturnsFalseWhenDisabled(): void
    {
        $filter = new ContentAltTextFilter(false, false);
        $this->assertFalse($filter->isEnabled());
    }

    public function testFilterReturnsEmptyContentUnchanged(): void
    {
        $this->assertSame('', $this->filter->filter(''));
    }

    public function testFilterReturnsContentWithNoImagesUnchanged(): void
    {
        $content = '<p>Hello world</p><div>No images here</div>';
        $this->assertSame($content, $this->filter->filter($content));
    }

    public function testFilterInjectsAltTextFromWpImageClass(): void
    {
        global $aatxt_test_post_meta;
        $aatxt_test_post_meta[42]['_wp_attachment_image_alt'] = 'A beautiful sunset';

        $content = '<p><img class="wp-image-42" src="https://example.com/image.jpg"></p>';
        $result = $this->filter->filter($content);

        $this->assertStringContainsString('alt="A beautiful sunset"', $result);
    }

    public function testFilterInjectsAltTextFromDataIdAttribute(): void
    {
        global $aatxt_test_post_meta;
        $aatxt_test_post_meta[55]['_wp_attachment_image_alt'] = 'Elementor image';

        $content = '<img data-id="55" src="https://example.com/photo.jpg">';
        $result = $this->filter->filter($content);

        $this->assertStringContainsString('alt="Elementor image"', $result);
    }

    public function testFilterInjectsAltTextFromSrcUrlFallback(): void
    {
        global $aatxt_test_post_meta, $aatxt_test_url_to_id;
        $url = 'https://example.com/wp-content/uploads/photo.jpg';
        $aatxt_test_url_to_id[$url] = 99;
        $aatxt_test_post_meta[99]['_wp_attachment_image_alt'] = 'Fallback alt text';

        $content = '<img src="' . $url . '">';
        $result = $this->filter->filter($content);

        $this->assertStringContainsString('alt="Fallback alt text"', $result);
    }

    public function testFilterSkipsImagesWithExistingAltTextWhenOverwriteDisabled(): void
    {
        global $aatxt_test_post_meta;
        $aatxt_test_post_meta[42]['_wp_attachment_image_alt'] = 'New alt text';

        $content = '<img alt="Existing alt" class="wp-image-42" src="https://example.com/image.jpg">';
        $result = $this->filter->filter($content);

        $this->assertStringContainsString('alt="Existing alt"', $result);
        $this->assertStringNotContainsString('New alt text', $result);
    }

    public function testFilterOverwritesExistingAltTextWhenOverwriteEnabled(): void
    {
        global $aatxt_test_post_meta;
        $aatxt_test_post_meta[42]['_wp_attachment_image_alt'] = 'New alt text';

        $content = '<img alt="Old alt" class="wp-image-42" src="https://example.com/image.jpg">';
        $result = $this->filterWithOverwrite->filter($content);

        $this->assertStringContainsString('alt="New alt text"', $result);
        $this->assertStringNotContainsString('Old alt', $result);
    }

    public function testFilterSkipsImagesWithNoResolvableAttachmentId(): void
    {
        $content = '<img src="https://external-site.com/image.jpg">';
        $result = $this->filter->filter($content);

        $this->assertSame($content, $result);
    }

    public function testFilterSkipsImagesWhenAttachmentHasNoAltText(): void
    {
        global $aatxt_test_post_meta;
        $aatxt_test_post_meta[42]['_wp_attachment_image_alt'] = '';

        $content = '<img class="wp-image-42" src="https://example.com/image.jpg">';
        $result = $this->filter->filter($content);

        $this->assertSame($content, $result);
    }

    public function testFilterHandlesMultipleImagesInContent(): void
    {
        global $aatxt_test_post_meta;
        $aatxt_test_post_meta[10]['_wp_attachment_image_alt'] = 'First image';
        $aatxt_test_post_meta[20]['_wp_attachment_image_alt'] = 'Second image';
        $aatxt_test_post_meta[30]['_wp_attachment_image_alt'] = '';

        $content = '<p><img class="wp-image-10" src="https://example.com/1.jpg"></p>'
            . '<p><img class="wp-image-20" src="https://example.com/2.jpg"></p>'
            . '<p><img class="wp-image-30" src="https://example.com/3.jpg"></p>';

        $result = $this->filter->filter($content);

        $this->assertStringContainsString('alt="First image"', $result);
        $this->assertStringContainsString('alt="Second image"', $result);
        // Third image has no alt text in meta, should remain unchanged
        $this->assertStringContainsString('class="wp-image-30"', $result);
        $this->assertEquals(2, substr_count($result, 'alt="'));
    }

    public function testFilterCachesUrlLookups(): void
    {
        global $aatxt_test_post_meta, $aatxt_test_url_to_id, $aatxt_test_url_lookup_count;
        $url = 'https://example.com/wp-content/uploads/cached.jpg';
        $aatxt_test_url_to_id[$url] = 77;
        $aatxt_test_post_meta[77]['_wp_attachment_image_alt'] = 'Cached result';

        $content = '<img src="' . $url . '"><img src="' . $url . '">';
        $this->filter->filter($content);

        // attachment_url_to_postid should be called only once thanks to caching
        $this->assertEquals(1, $aatxt_test_url_lookup_count);
    }

    public function testFilterHandlesEmptyAltAttributeAsIntentionallySet(): void
    {
        global $aatxt_test_post_meta;
        $aatxt_test_post_meta[42]['_wp_attachment_image_alt'] = 'New alt text';

        // Empty alt="" is W3C pattern for decorative images, should be respected
        $content = '<img alt="" class="wp-image-42" src="https://example.com/image.jpg">';
        $result = $this->filter->filter($content);

        $this->assertStringContainsString('alt=""', $result);
        $this->assertStringNotContainsString('New alt text', $result);
    }

    public function testFilterOverwritesEmptyAltWhenOverwriteEnabled(): void
    {
        global $aatxt_test_post_meta;
        $aatxt_test_post_meta[42]['_wp_attachment_image_alt'] = 'New alt text';

        $content = '<img alt="" class="wp-image-42" src="https://example.com/image.jpg">';
        $result = $this->filterWithOverwrite->filter($content);

        $this->assertStringContainsString('alt="New alt text"', $result);
    }

    public function testFilterHandlesSingleQuotedAttributes(): void
    {
        global $aatxt_test_post_meta;
        $aatxt_test_post_meta[42]['_wp_attachment_image_alt'] = 'Single quoted';

        $content = "<img class='size-full wp-image-42' src='https://example.com/image.jpg'>";
        $result = $this->filter->filter($content);

        $this->assertStringContainsString('alt="Single quoted"', $result);
    }

    public function testFilterHandlesSelfClosingTags(): void
    {
        global $aatxt_test_post_meta;
        $aatxt_test_post_meta[42]['_wp_attachment_image_alt'] = 'Self closing';

        $content = '<img class="wp-image-42" src="https://example.com/image.jpg" />';
        $result = $this->filter->filter($content);

        $this->assertStringContainsString('alt="Self closing"', $result);
        // Should still be valid HTML
        $this->assertStringContainsString('/>', $result);
    }

    public function testFilterPrefersWpImageClassOverDataId(): void
    {
        global $aatxt_test_post_meta;
        $aatxt_test_post_meta[42]['_wp_attachment_image_alt'] = 'From class';
        $aatxt_test_post_meta[55]['_wp_attachment_image_alt'] = 'From data-id';

        // Image has both wp-image-42 and data-id="55" — class should win
        $content = '<img class="wp-image-42" data-id="55" src="https://example.com/image.jpg">';
        $result = $this->filter->filter($content);

        $this->assertStringContainsString('alt="From class"', $result);
    }

    public function testFilterEscapesAltTextSpecialCharacters(): void
    {
        global $aatxt_test_post_meta;
        $aatxt_test_post_meta[42]['_wp_attachment_image_alt'] = 'Image with "quotes" & <special> chars';

        $content = '<img class="wp-image-42" src="https://example.com/image.jpg">';
        $result = $this->filter->filter($content);

        $this->assertStringContainsString('alt="Image with &quot;quotes&quot; &amp; &lt;special&gt; chars"', $result);
    }
}
