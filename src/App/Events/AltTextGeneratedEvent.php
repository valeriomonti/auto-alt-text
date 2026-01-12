<?php

namespace AATXT\App\Events;

/**
 * Event dispatched when alt text is successfully generated.
 *
 * This event is fired after an AI provider successfully generates
 * alt text for an image. Listeners can use this for logging,
 * analytics, or triggering additional actions.
 *
 * @package AATXT\App\Events
 */
final class AltTextGeneratedEvent
{
    /**
     * WordPress attachment ID
     *
     * @var int
     */
    private $imageId;

    /**
     * Generated alt text
     *
     * @var string
     */
    private $altText;

    /**
     * Provider name that generated the alt text
     *
     * @var string
     */
    private $provider;

    /**
     * Timestamp when the event occurred
     *
     * @var int
     */
    private $timestamp;

    /**
     * Constructor
     *
     * @param int $imageId WordPress attachment ID
     * @param string $altText Generated alt text
     * @param string $provider Provider name (e.g., 'OpenAI Vision', 'Anthropic')
     */
    public function __construct(int $imageId, string $altText, string $provider)
    {
        $this->imageId = $imageId;
        $this->altText = $altText;
        $this->provider = $provider;
        $this->timestamp = time();
    }

    /**
     * Get the image ID.
     *
     * @return int
     */
    public function getImageId(): int
    {
        return $this->imageId;
    }

    /**
     * Get the generated alt text.
     *
     * @return string
     */
    public function getAltText(): string
    {
        return $this->altText;
    }

    /**
     * Get the provider name.
     *
     * @return string
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Get the timestamp when the event occurred.
     *
     * @return int Unix timestamp
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * Get the length of the generated alt text.
     *
     * @return int Character count
     */
    public function getAltTextLength(): int
    {
        return mb_strlen($this->altText);
    }
}
