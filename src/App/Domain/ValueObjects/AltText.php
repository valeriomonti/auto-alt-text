<?php

namespace AATXT\App\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object representing alt text for an image.
 *
 * This immutable object ensures that alt text values are properly
 * validated and constrained to reasonable lengths for SEO purposes.
 */
final class AltText
{
    /**
     * Maximum recommended length for alt text (SEO best practice)
     */
    public const MAX_LENGTH = 125;

    /**
     * The alt text value
     *
     * @var string
     */
    private $value;

    /**
     * Private constructor to enforce factory method usage.
     *
     * @param string $value The alt text
     */
    private function __construct(string $value)
    {
        $this->value = trim($value);
    }

    /**
     * Create an AltText from a string value.
     *
     * @param string $value The alt text string
     * @return self
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Create an empty AltText.
     *
     * @return self
     */
    public static function empty(): self
    {
        return new self('');
    }

    /**
     * Get the string value of the alt text.
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Create a new AltText truncated to the specified length.
     *
     * If the alt text is already shorter than the specified length,
     * returns the same instance.
     *
     * @param int $length Maximum length (defaults to MAX_LENGTH)
     * @return self New truncated AltText instance
     * @throws InvalidArgumentException If length is not positive
     */
    public function truncate(int $length = self::MAX_LENGTH): self
    {
        if ($length <= 0) {
            throw new InvalidArgumentException(
                sprintf('Truncate length must be positive, got: %d', $length)
            );
        }

        if (mb_strlen($this->value) <= $length) {
            return $this;
        }

        // Truncate at word boundary if possible
        $truncated = mb_substr($this->value, 0, $length);
        $lastSpace = mb_strrpos($truncated, ' ');

        if ($lastSpace !== false && $lastSpace > $length * 0.8) {
            // Only truncate at word boundary if we're not losing too much text
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }

        return new self($truncated);
    }

    /**
     * Check if the alt text is empty.
     *
     * @return bool True if empty or whitespace-only
     */
    public function isEmpty(): bool
    {
        return $this->value === '';
    }

    /**
     * Check if the alt text exceeds the recommended maximum length.
     *
     * @return bool True if exceeds MAX_LENGTH
     */
    public function exceedsMaxLength(): bool
    {
        return mb_strlen($this->value) > self::MAX_LENGTH;
    }

    /**
     * Get the length of the alt text.
     *
     * @return int Character count
     */
    public function length(): int
    {
        return mb_strlen($this->value);
    }

    /**
     * Check if this AltText equals another AltText.
     *
     * @param AltText $other The other AltText to compare
     * @return bool True if equal, false otherwise
     */
    public function equals(AltText $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * String representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
