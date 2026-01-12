<?php

namespace AATXT\App\Services;

use AATXT\App\Admin\PluginOptions;
use AATXT\App\AltTextGeneratorAi;
use AATXT\App\AltTextGeneratorAttachmentTitle;
use AATXT\App\Domain\Exceptions\UnsupportedGeneratorException;
use AATXT\App\Events\AltTextGeneratedEvent;
use AATXT\App\Events\AltTextGenerationFailedEvent;
use AATXT\App\Events\EventDispatcherInterface;
use AATXT\App\Exceptions\Anthropic\AnthropicException;
use AATXT\App\Exceptions\Azure\AzureException;
use AATXT\App\Exceptions\OpenAI\OpenAIException;
use AATXT\App\Infrastructure\Repositories\ConfigRepositoryInterface;
use AATXT\App\Infrastructure\Repositories\ErrorLogRepositoryInterface;
use AATXT\Config\Constants;
use Exception;

/**
 * Service for generating alt text for WordPress attachments.
 *
 * This service encapsulates all the business logic for alt text generation,
 * using the Factory pattern to create appropriate generators and handling
 * error logging.
 *
 * If the selected AI provider fails, the error is logged and no alt text is generated.
 */
final class AltTextService
{
    /**
     * Factory for creating alt text generators
     *
     * @var AltTextGeneratorFactory
     */
    private $factory;

    /**
     * Configuration repository
     *
     * @var ConfigRepositoryInterface
     */
    private $config;

    /**
     * Error log repository
     *
     * @var ErrorLogRepositoryInterface
     */
    private $errorLog;

    /**
     * MIME type validation map
     *
     * @var array<string, array<string>>
     */
    private $allowedMimeTypes;

    /**
     * Event dispatcher for publishing events (optional)
     *
     * @var EventDispatcherInterface|null
     */
    private $eventDispatcher;

    /**
     * Constructor
     *
     * @param AltTextGeneratorFactory $factory Factory for creating generators
     * @param ConfigRepositoryInterface $config Configuration repository
     * @param ErrorLogRepositoryInterface $errorLog Error logging repository
     * @param EventDispatcherInterface|null $eventDispatcher Event dispatcher (optional)
     */
    public function __construct(
        AltTextGeneratorFactory $factory,
        ConfigRepositoryInterface $config,
        ErrorLogRepositoryInterface $errorLog,
        ?EventDispatcherInterface $eventDispatcher = null
    ) {
        $this->factory = $factory;
        $this->config = $config;
        $this->errorLog = $errorLog;
        $this->eventDispatcher = $eventDispatcher;

        // Map of allowed MIME types per generator type
        $this->allowedMimeTypes = [
            Constants::AATXT_OPTION_TYPOLOGY_CHOICE_OPENAI => Constants::AATXT_OPENAI_ALLOWED_MIME_TYPES,
            Constants::AATXT_OPTION_TYPOLOGY_CHOICE_ANTHROPIC => Constants::AATXT_ANTHROPIC_ALLOWED_MIME_TYPES,
            Constants::AATXT_OPTION_TYPOLOGY_CHOICE_AZURE => Constants::AATXT_AZURE_ALLOWED_MIME_TYPES,
        ];
    }

    /**
     * Set the event dispatcher.
     *
     * @param EventDispatcherInterface $eventDispatcher The event dispatcher
     * @return self Fluent interface
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): self
    {
        $this->eventDispatcher = $eventDispatcher;
        return $this;
    }

    /**
     * Generate alt text for a WordPress attachment.
     *
     * This method orchestrates the entire alt text generation process:
     * - Validates the attachment is an image
     * - Checks if existing alt text should be preserved
     * - Validates MIME type for AI providers
     * - Creates appropriate generator via factory
     * - Handles errors and logging
     *
     * If the selected provider fails, the error is logged and an empty string is returned.
     *
     * @param int $attachmentId WordPress attachment post ID
     * @return string Generated alt text, or empty string if generation fails
     */
    public function generateForAttachment(int $attachmentId): string
    {
        // Verify it's an image attachment
        if (!wp_attachment_is_image($attachmentId)) {
            return '';
        }

        // Check if we should preserve existing alt text
        if (PluginOptions::preserveExistingAltText()) {
            $existingAltText = get_post_meta($attachmentId, '_wp_attachment_image_alt', true);

            if (!empty($existingAltText)) {
                return $existingAltText;
            }
        }

        $typology = PluginOptions::typology();

        // Deactivated - return empty
        if ($typology === Constants::AATXT_OPTION_TYPOLOGY_DEACTIVATED) {
            return '';
        }

        // Special handling for article title typology (has fallback logic to attachment title)
        if ($typology === Constants::AATXT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE) {
            return $this->generateFromArticleTitle($attachmentId);
        }

        // Validate MIME type for AI providers
        if ($this->requiresMimeTypeValidation($typology)) {
            $mimeType = get_post_mime_type($attachmentId);
            if (!$this->isMimeTypeSupported($typology, $mimeType)) {
                $this->logMimeTypeError($attachmentId, $typology);
                return '';
            }
        }

        // Generate alt text using the factory
        try {
            $generator = $this->factory->create($typology);
            $altText = $generator->altText($attachmentId);

            // Dispatch success event
            $this->dispatchSuccessEvent($attachmentId, $altText, $typology);

            return $altText;
        } catch (UnsupportedGeneratorException $e) {
            // Typology not registered in factory
            return '';
        } catch (OpenAIException $e) {
            $this->logError($attachmentId, 'OpenAI', $e->getMessage());
            $this->dispatchFailureEvent($attachmentId, 'OpenAI', $e);
            return '';
        } catch (AnthropicException $e) {
            $this->logError($attachmentId, 'Anthropic', $e->getMessage());
            $this->dispatchFailureEvent($attachmentId, 'Anthropic', $e);
            return '';
        } catch (AzureException $e) {
            $this->logError($attachmentId, 'Azure', $e->getMessage());
            $this->dispatchFailureEvent($attachmentId, 'Azure', $e);
            return '';
        } catch (Exception $e) {
            $this->logError($attachmentId, 'Unknown', $e->getMessage());
            $this->dispatchFailureEvent($attachmentId, 'Unknown', $e);
            return '';
        }
    }

    /**
     * Generate alt text from article title with fallback to attachment title.
     *
     * @param int $attachmentId WordPress attachment ID
     * @return string Generated alt text
     */
    private function generateFromArticleTitle(int $attachmentId): string
    {
        $parentId = wp_get_post_parent_id($attachmentId);

        if ($parentId) {
            // Has parent post - use parent post title
            try {
                $generator = $this->factory->create(Constants::AATXT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE);
                return $generator->altText($attachmentId);
            } catch (Exception $e) {
                // Fallback to attachment title if parent title fails
                return $this->generateFromAttachmentTitle($attachmentId);
            }
        }

        // No parent post - use attachment title as fallback
        return $this->generateFromAttachmentTitle($attachmentId);
    }

    /**
     * Generate alt text from attachment title.
     *
     * @param int $attachmentId WordPress attachment ID
     * @return string Generated alt text
     */
    private function generateFromAttachmentTitle(int $attachmentId): string
    {
        try {
            $generator = $this->factory->create(Constants::AATXT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE);
            return $generator->altText($attachmentId);
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Check if a typology requires MIME type validation.
     *
     * @param string $typology Generator typology
     * @return bool True if MIME validation required
     */
    private function requiresMimeTypeValidation(string $typology): bool
    {
        return isset($this->allowedMimeTypes[$typology]);
    }

    /**
     * Check if a MIME type is supported for the given typology.
     *
     * @param string $typology Generator typology
     * @param string $mimeType MIME type to check
     * @return bool True if supported
     */
    private function isMimeTypeSupported(string $typology, string $mimeType): bool
    {
        $allowedTypes = $this->allowedMimeTypes[$typology] ?? [];
        return in_array($mimeType, $allowedTypes, true);
    }

    /**
     * Log MIME type validation error.
     *
     * @param int $attachmentId WordPress attachment ID
     * @param string $typology Generator typology
     * @return void
     */
    private function logMimeTypeError(int $attachmentId, string $typology): void
    {
        $allowedTypes = $this->allowedMimeTypes[$typology] ?? [];
        $formats = $this->formatMimeTypeList($allowedTypes);
        $message = "You uploaded an unsupported image. Please make sure your image has one of the following formats: $formats";

        $this->logError($attachmentId, $typology, $message);
    }

    /**
     * Format MIME type list for display.
     *
     * @param array<string> $mimeTypes List of MIME types
     * @return string Formatted string (e.g., "png, jpeg, gif")
     */
    private function formatMimeTypeList(array $mimeTypes): string
    {
        return str_replace('image/', '', implode(', ', $mimeTypes));
    }

    /**
     * Log an error to the error repository.
     *
     * @param int $imageId WordPress attachment ID
     * @param string $provider Provider name
     * @param string $message Error message
     * @return void
     */
    private function logError(int $imageId, string $provider, string $message): void
    {
        $errorMessage = $provider . ' - ' . $message;

        $errorLog = new \AATXT\App\Domain\Entities\ErrorLog($imageId, $errorMessage);
        $this->errorLog->save($errorLog);
    }

    /**
     * Dispatch an event if the event dispatcher is available.
     *
     * @param object $event The event to dispatch
     * @return void
     */
    private function dispatchEvent(object $event): void
    {
        if ($this->eventDispatcher !== null) {
            $this->eventDispatcher->dispatch($event);
        }
    }

    /**
     * Dispatch a success event after alt text generation.
     *
     * @param int $imageId WordPress attachment ID
     * @param string $altText Generated alt text
     * @param string $provider Provider name
     * @return void
     */
    private function dispatchSuccessEvent(int $imageId, string $altText, string $provider): void
    {
        if ($altText !== '') {
            $this->dispatchEvent(new AltTextGeneratedEvent($imageId, $altText, $provider));
        }
    }

    /**
     * Dispatch a failure event after alt text generation fails.
     *
     * @param int $imageId WordPress attachment ID
     * @param string $provider Provider name
     * @param Exception $exception The exception that caused the failure
     * @return void
     */
    private function dispatchFailureEvent(int $imageId, string $provider, Exception $exception): void
    {
        $this->dispatchEvent(new AltTextGenerationFailedEvent($imageId, $provider, $exception));
    }
}
