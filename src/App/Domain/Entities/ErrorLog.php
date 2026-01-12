<?php

namespace AATXT\App\Domain\Entities;

use AATXT\App\Domain\ValueObjects\ImageId;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * ErrorLog Entity
 *
 * Represents a single error log entry in the domain model.
 * This is an immutable entity that encapsulates error log data.
 */
final class ErrorLog
{
    /**
     * Error log ID
     *
     * @var int|null
     */
    private $id;

    /**
     * WordPress attachment/image ID
     *
     * @var ImageId
     */
    private $imageId;

    /**
     * Error message text
     *
     * @var string
     */
    private $errorMessage;

    /**
     * Timestamp when error occurred
     *
     * @var DateTimeImmutable
     */
    private $occurredAt;

    /**
     * Constructor
     *
     * @param int|ImageId $imageId WordPress attachment ID (accepts both int and ImageId for backward compatibility)
     * @param string $errorMessage Error message text
     * @param DateTimeImmutable|null $occurredAt Timestamp (defaults to current time)
     * @param int|null $id Database record ID (null for new records)
     *
     * @throws InvalidArgumentException If imageId is invalid or errorMessage is empty
     */
    public function __construct(
        $imageId,
        string $errorMessage,
        ?DateTimeImmutable $occurredAt = null,
        ?int $id = null
    ) {
        // Accept both int and ImageId for backward compatibility
        if ($imageId instanceof ImageId) {
            $this->imageId = $imageId;
        } elseif (is_int($imageId)) {
            $this->imageId = ImageId::fromInt($imageId);
        } else {
            throw new InvalidArgumentException('Image ID must be an integer or ImageId instance');
        }

        if (empty(trim($errorMessage))) {
            throw new InvalidArgumentException('Error message cannot be empty');
        }

        $this->id = $id;
        $this->errorMessage = trim($errorMessage);
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable();
    }

    /**
     * Create ErrorLog from database row
     *
     * Factory method to construct an ErrorLog entity from a database result row.
     *
     * @param array $row Associative array with keys: id, image_id, error_message, time
     * @return self
     *
     * @throws InvalidArgumentException If required fields are missing
     */
    public static function fromDatabaseRow(array $row): self
    {
        if (!isset($row['image_id'], $row['error_message'], $row['time'])) {
            throw new InvalidArgumentException('Missing required fields in database row');
        }

        $occurredAt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row['time']);

        if ($occurredAt === false) {
            // Fallback to current time if parsing fails
            $occurredAt = new DateTimeImmutable();
        }

        return new self(
            ImageId::fromInt((int) $row['image_id']),
            (string) $row['error_message'],
            $occurredAt,
            isset($row['id']) ? (int) $row['id'] : null
        );
    }

    /**
     * Get error log ID
     *
     * @return int|null Database record ID, or null if not persisted
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get image/attachment ID as integer.
     *
     * @return int WordPress attachment ID
     */
    public function getImageId(): int
    {
        return $this->imageId->toInt();
    }

    /**
     * Get image/attachment ID as ImageId Value Object.
     *
     * @return ImageId The ImageId value object
     */
    public function getImageIdObject(): ImageId
    {
        return $this->imageId;
    }

    /**
     * Get error message
     *
     * @return string Error message text
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * Get timestamp when error occurred
     *
     * @return DateTimeImmutable Immutable datetime object
     */
    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /**
     * Check if this error log has been persisted to database
     *
     * @return bool True if record has an ID, false otherwise
     */
    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    /**
     * Get formatted timestamp for database storage
     *
     * @return string Formatted datetime string (Y-m-d H:i:s)
     */
    public function getFormattedTime(): string
    {
        return $this->occurredAt->format('Y-m-d H:i:s');
    }
}
