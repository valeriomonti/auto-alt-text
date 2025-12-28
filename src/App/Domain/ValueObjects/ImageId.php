<?php

namespace AATXT\App\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object representing a WordPress attachment/image ID.
 *
 * This immutable object ensures that image IDs are always valid
 * (positive integers) throughout the application.
 */
final class ImageId
{
    /**
     * The image ID value
     *
     * @var int
     */
    private $value;

    /**
     * Private constructor to enforce factory method usage.
     *
     * @param int $value The image ID
     * @throws InvalidArgumentException If value is not positive
     */
    private function __construct(int $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException(
                sprintf('Image ID must be a positive integer, got: %d', $value)
            );
        }

        $this->value = $value;
    }

    /**
     * Create an ImageId from an integer value.
     *
     * @param int $value The image ID
     * @return self
     * @throws InvalidArgumentException If value is not positive
     */
    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    /**
     * Get the integer value of the image ID.
     *
     * @return int
     */
    public function toInt(): int
    {
        return $this->value;
    }

    /**
     * Check if this ImageId equals another ImageId.
     *
     * @param ImageId $other The other ImageId to compare
     * @return bool True if equal, false otherwise
     */
    public function equals(ImageId $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * String representation for debugging.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->value;
    }
}
