<?php

declare(strict_types=1);

namespace AATXT\Tests\Unit;

use AATXT\App\AIProviders\AIProviderInterface;
use AATXT\App\AltTextGeneratorAi;
use PHPUnit\Framework\TestCase;

/**
 * Stub for WordPress wp_get_attachment_url function
 * This function is called by AltTextGeneratorAi::altText()
 *
 * @param int $attachmentId
 * @return string
 */
function wp_get_attachment_url(int $attachmentId): string
{
    // Return a predictable URL based on the attachment ID
    return 'https://example.com/wp-content/uploads/image-' . $attachmentId . '.jpg';
}

// Make the function available in the AATXT\App namespace where it's called
namespace AATXT\App;

/**
 * Stub for WordPress wp_get_attachment_url function
 *
 * @param int $attachmentId
 * @return string
 */
function wp_get_attachment_url(int $attachmentId): string
{
    return 'https://example.com/wp-content/uploads/image-' . $attachmentId . '.jpg';
}

namespace AATXT\Tests\Unit;

use AATXT\App\AIProviders\AIProviderInterface;
use AATXT\App\AltTextGeneratorAi;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AltTextGeneratorAi with mocked AI provider.
 *
 * These tests verify the AltTextGeneratorAi behavior without WordPress.
 * The wp_get_attachment_url function is stubbed to return predictable URLs.
 *
 * @coversDefaultClass \AATXT\App\AltTextGeneratorAi
 */
class AltTextGeneratorAiTest extends TestCase
{
    /**
     * @test
     * @covers ::make
     * @covers ::altText
     */
    public function it_generates_alt_text_using_ai_provider(): void
    {
        // Arrange
        $imageId = 123;
        $expectedUrl = 'https://example.com/wp-content/uploads/image-123.jpg';
        $expectedAltText = 'A beautiful sunset over the mountains';

        $aiProvider = $this->createMock(AIProviderInterface::class);
        $aiProvider->expects($this->once())
            ->method('response')
            ->with($expectedUrl)
            ->willReturn($expectedAltText);

        $generator = AltTextGeneratorAi::make($aiProvider);

        // Act
        $result = $generator->altText($imageId);

        // Assert
        $this->assertEquals($expectedAltText, $result);
    }

    /**
     * @test
     * @covers ::make
     */
    public function it_creates_instance_via_factory_method(): void
    {
        // Arrange
        $aiProvider = $this->createMock(AIProviderInterface::class);

        // Act
        $generator = AltTextGeneratorAi::make($aiProvider);

        // Assert
        $this->assertInstanceOf(AltTextGeneratorAi::class, $generator);
    }

    /**
     * @test
     * @covers ::altText
     */
    public function it_passes_correct_image_url_to_provider(): void
    {
        // Arrange
        $imageId = 456;
        $expectedUrl = 'https://example.com/wp-content/uploads/image-456.jpg';

        $aiProvider = $this->createMock(AIProviderInterface::class);
        $aiProvider->expects($this->once())
            ->method('response')
            ->with($this->equalTo($expectedUrl))
            ->willReturn('Test alt text');

        $generator = AltTextGeneratorAi::make($aiProvider);

        // Act
        $generator->altText($imageId);
    }

    /**
     * @test
     * @covers ::altText
     */
    public function it_returns_empty_string_when_provider_returns_empty(): void
    {
        // Arrange
        $aiProvider = $this->createMock(AIProviderInterface::class);
        $aiProvider->method('response')->willReturn('');

        $generator = AltTextGeneratorAi::make($aiProvider);

        // Act
        $result = $generator->altText(123);

        // Assert
        $this->assertEquals('', $result);
    }

    /**
     * @test
     * @covers ::altText
     */
    public function it_propagates_exception_from_provider(): void
    {
        // Arrange
        $aiProvider = $this->createMock(AIProviderInterface::class);
        $aiProvider->method('response')
            ->willThrowException(new \Exception('API Error'));

        $generator = AltTextGeneratorAi::make($aiProvider);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('API Error');

        // Act
        $generator->altText(123);
    }

    /**
     * @test
     * @covers ::altText
     */
    public function it_handles_different_image_ids(): void
    {
        // Arrange
        $aiProvider = $this->createMock(AIProviderInterface::class);
        $aiProvider->method('response')
            ->willReturnCallback(function ($url) {
                // Return different alt text based on URL
                if (strpos($url, 'image-1.jpg') !== false) {
                    return 'Alt text for image 1';
                }
                if (strpos($url, 'image-2.jpg') !== false) {
                    return 'Alt text for image 2';
                }
                return 'Unknown image';
            });

        $generator = AltTextGeneratorAi::make($aiProvider);

        // Act & Assert
        $this->assertEquals('Alt text for image 1', $generator->altText(1));
        $this->assertEquals('Alt text for image 2', $generator->altText(2));
    }

    /**
     * @test
     * @covers ::make
     */
    public function it_can_use_different_providers(): void
    {
        // Arrange
        $provider1 = $this->createMock(AIProviderInterface::class);
        $provider1->method('response')->willReturn('Response from provider 1');

        $provider2 = $this->createMock(AIProviderInterface::class);
        $provider2->method('response')->willReturn('Response from provider 2');

        $generator1 = AltTextGeneratorAi::make($provider1);
        $generator2 = AltTextGeneratorAi::make($provider2);

        // Act & Assert
        $this->assertEquals('Response from provider 1', $generator1->altText(1));
        $this->assertEquals('Response from provider 2', $generator2->altText(1));
    }
}
