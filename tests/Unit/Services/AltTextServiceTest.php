<?php

declare(strict_types=1);

namespace AATXT\App\Services;

/**
 * Stubs for the WordPress functions called by AltTextService::generateForAttachment().
 * Declared in the service namespace so PHP resolves them before the global ones.
 */

function wp_attachment_is_image(int $attachmentId): bool
{
    return true;
}

function get_post_meta(int $postId, string $key, bool $single = false): string
{
    return '';
}

function get_post_mime_type(int $postId): string
{
    return 'image/jpeg';
}

namespace AATXT\App\Admin;

/**
 * Stub for get_option(), called by the PluginOptions static helpers
 * (typology(), preserveExistingAltText()) used by the service.
 *
 * @param string $option
 * @param mixed $default
 * @return mixed
 */
function get_option($option, $default = false)
{
    return $GLOBALS['aatxt_test_options'][$option] ?? $default;
}

namespace AATXT\Tests\Unit\Services;

use AATXT\App\AltTextGeneratorInterface;
use AATXT\App\Events\AltTextGenerationFailedEvent;
use AATXT\App\Events\Listeners\LogErrorListener;
use AATXT\App\Events\SimpleEventDispatcher;
use AATXT\App\Exceptions\Gemini\GeminiException;
use AATXT\App\Infrastructure\Repositories\ConfigRepositoryInterface;
use AATXT\App\Infrastructure\Repositories\ErrorLogRepositoryInterface;
use AATXT\App\Services\AltTextGeneratorFactory;
use AATXT\App\Services\AltTextService;
use AATXT\Config\Constants;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AltTextService error handling.
 *
 * These tests focus on the guarantee that a provider failure is persisted to
 * the error log exactly once, regardless of how the event system is wired.
 * Before the fix, the service both wrote the error directly and dispatched a
 * failure event that LogErrorListener also persisted, producing two identical
 * rows in the error log for every failure.
 *
 * @coversDefaultClass \AATXT\App\Services\AltTextService
 */
class AltTextServiceTest extends TestCase
{
    private const IMAGE_ID = 2587;

    private const ERROR_MESSAGE = 'HTTP request failed: HTTP request returned status 429';

    protected function setUp(): void
    {
        $GLOBALS['aatxt_test_options'] = [
            Constants::AATXT_OPTION_FIELD_TYPOLOGY => Constants::AATXT_OPTION_TYPOLOGY_CHOICE_GEMINI,
            Constants::AATXT_OPTION_FIELD_PRESERVE_EXISTING_ALT_TEXT => false,
        ];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['aatxt_test_options']);
    }

    /**
     * A failing provider must produce a single error log row when the event
     * dispatcher has LogErrorListener registered, as the container wires it.
     *
     * @covers ::generateForAttachment
     */
    public function testItLogsAProviderFailureOnlyOnce(): void
    {
        $errorLog = $this->createErrorLogRepository();
        $errorLog->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($log) {
                return $log->getImageId() === self::IMAGE_ID
                    && strpos($log->getErrorMessage(), 'Gemini - ') === 0;
            }));

        $dispatcher = new SimpleEventDispatcher();
        $dispatcher->listen(
            AltTextGenerationFailedEvent::class,
            [new LogErrorListener($errorLog), 'handle']
        );

        $service = new AltTextService(
            $this->createFailingFactory(),
            $this->createStub(ConfigRepositoryInterface::class),
            $errorLog,
            $dispatcher
        );

        $this->assertSame('', $service->generateForAttachment(self::IMAGE_ID));
    }

    /**
     * With no dispatcher at all the service must still persist the error,
     * otherwise the fix would silently swallow every failure.
     *
     * @covers ::generateForAttachment
     */
    public function testItLogsTheFailureWhenNoDispatcherIsConfigured(): void
    {
        $errorLog = $this->createErrorLogRepository();
        $errorLog->expects($this->once())->method('save');

        $service = new AltTextService(
            $this->createFailingFactory(),
            $this->createStub(ConfigRepositoryInterface::class),
            $errorLog
        );

        $this->assertSame('', $service->generateForAttachment(self::IMAGE_ID));
    }

    /**
     * A dispatcher without any listener persisting the error must fall back to
     * the direct write, so the error is logged exactly once either way.
     *
     * @covers ::generateForAttachment
     */
    public function testItLogsTheFailureWhenNoListenerHandlesTheEvent(): void
    {
        $errorLog = $this->createErrorLogRepository();
        $errorLog->expects($this->once())->method('save');

        $service = new AltTextService(
            $this->createFailingFactory(),
            $this->createStub(ConfigRepositoryInterface::class),
            $errorLog,
            new SimpleEventDispatcher()
        );

        $this->assertSame('', $service->generateForAttachment(self::IMAGE_ID));
    }

    /**
     * A successful generation must not touch the error log.
     *
     * @covers ::generateForAttachment
     */
    public function testItDoesNotLogAnythingOnSuccess(): void
    {
        $errorLog = $this->createErrorLogRepository();
        $errorLog->expects($this->never())->method('save');

        $generator = $this->createStub(AltTextGeneratorInterface::class);
        $generator->method('altText')->willReturn('A cat sleeping on a sofa');

        $factory = $this->createStub(AltTextGeneratorFactory::class);
        $factory->method('create')->willReturn($generator);

        $service = new AltTextService(
            $factory,
            $this->createStub(ConfigRepositoryInterface::class),
            $errorLog,
            new SimpleEventDispatcher()
        );

        $this->assertSame('A cat sleeping on a sofa', $service->generateForAttachment(self::IMAGE_ID));
    }

    /**
     * @return ErrorLogRepositoryInterface&MockObject
     */
    private function createErrorLogRepository(): MockObject
    {
        return $this->createMock(ErrorLogRepositoryInterface::class);
    }

    /**
     * Build a factory whose generator always fails with a Gemini error,
     * mirroring the 429 returned when the API quota is exhausted.
     *
     * @return AltTextGeneratorFactory
     */
    private function createFailingFactory(): AltTextGeneratorFactory
    {
        $generator = $this->createStub(AltTextGeneratorInterface::class);
        $generator->method('altText')
            ->willThrowException(new GeminiException(self::ERROR_MESSAGE));

        $factory = $this->createStub(AltTextGeneratorFactory::class);
        $factory->method('create')->willReturn($generator);

        return $factory;
    }
}
