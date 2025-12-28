<?php

namespace AATXT\Tests\Unit\Services;

use AATXT\App\Services\ImageValidator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ImageValidator
 *
 * Note: Tests that require WordPress functions are in Integration/Services/ImageValidatorTest.php
 */
class ImageValidatorTest extends TestCase
{
    /**
     * @var ImageValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new ImageValidator();
    }

    /**
     * Test validateMimeType returns true for allowed type
     */
    public function testValidateMimeTypeReturnsTrueForAllowedType(): void
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

        $result = $this->validator->validateMimeType('image/jpeg', $allowedTypes);

        $this->assertTrue($result);
    }

    /**
     * Test validateMimeType returns false for disallowed type
     */
    public function testValidateMimeTypeReturnsFalseForDisallowedType(): void
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

        $result = $this->validator->validateMimeType('image/gif', $allowedTypes);

        $this->assertFalse($result);
    }

    /**
     * Test validateMimeType returns false for empty allowed types
     */
    public function testValidateMimeTypeReturnsFalseForEmptyAllowedTypes(): void
    {
        $result = $this->validator->validateMimeType('image/jpeg', []);

        $this->assertFalse($result);
    }

    /**
     * Test validateMimeType is case sensitive
     */
    public function testValidateMimeTypeIsCaseSensitive(): void
    {
        $allowedTypes = ['image/jpeg'];

        $this->assertTrue($this->validator->validateMimeType('image/jpeg', $allowedTypes));
        $this->assertFalse($this->validator->validateMimeType('IMAGE/JPEG', $allowedTypes));
        $this->assertFalse($this->validator->validateMimeType('Image/Jpeg', $allowedTypes));
    }

    /**
     * Test validateMimeType with empty MIME type string
     */
    public function testValidateMimeTypeWithEmptyMimeType(): void
    {
        $allowedTypes = ['image/jpeg', 'image/png'];

        $result = $this->validator->validateMimeType('', $allowedTypes);

        $this->assertFalse($result);
    }

    /**
     * Test validateMimeType uses strict comparison
     */
    public function testValidateMimeTypeUsesStrictComparison(): void
    {
        $allowedTypes = ['0', '1', 'image/jpeg'];

        // Should not match due to type coercion issues
        $this->assertFalse($this->validator->validateMimeType('0', []));
        $this->assertTrue($this->validator->validateMimeType('0', $allowedTypes));
    }

    /**
     * Test formatMimeTypeList converts to human readable format
     */
    public function testFormatMimeTypeListConvertsToHumanReadable(): void
    {
        $mimeTypes = ['image/png', 'image/jpeg', 'image/gif'];

        $result = $this->validator->formatMimeTypeList($mimeTypes);

        $this->assertEquals('png, jpeg, gif', $result);
    }

    /**
     * Test formatMimeTypeList with single type
     */
    public function testFormatMimeTypeListWithSingleType(): void
    {
        $mimeTypes = ['image/png'];

        $result = $this->validator->formatMimeTypeList($mimeTypes);

        $this->assertEquals('png', $result);
    }

    /**
     * Test formatMimeTypeList with empty array
     */
    public function testFormatMimeTypeListWithEmptyArray(): void
    {
        $result = $this->validator->formatMimeTypeList([]);

        $this->assertEquals('', $result);
    }

    /**
     * Test formatMimeTypeList with webp type
     */
    public function testFormatMimeTypeListWithWebp(): void
    {
        $mimeTypes = ['image/webp', 'image/avif'];

        $result = $this->validator->formatMimeTypeList($mimeTypes);

        $this->assertEquals('webp, avif', $result);
    }

    /**
     * Test formatMimeTypeList handles non-image MIME types
     */
    public function testFormatMimeTypeListHandlesNonImageMimeTypes(): void
    {
        $mimeTypes = ['application/pdf', 'text/plain'];

        $result = $this->validator->formatMimeTypeList($mimeTypes);

        // Note: this method is specifically designed for image/* types
        // but will work with any string by removing "image/" prefix
        $this->assertEquals('application/pdf, text/plain', $result);
    }

    /**
     * Test formatMimeTypeList with mixed image types
     */
    public function testFormatMimeTypeListWithAllCommonImageTypes(): void
    {
        $mimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/heic'];

        $result = $this->validator->formatMimeTypeList($mimeTypes);

        $this->assertEquals('jpeg, jpg, png, gif, webp, heic', $result);
    }
}
