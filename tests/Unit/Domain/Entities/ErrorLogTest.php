<?php

namespace AATXT\Tests\Unit\Domain\Entities;

use AATXT\App\Domain\Entities\ErrorLog;
use AATXT\App\Domain\ValueObjects\ImageId;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the ErrorLog Entity
 */
class ErrorLogTest extends TestCase
{
    /**
     * Test creating ErrorLog with integer imageId
     */
    public function testConstructorWithIntegerImageId(): void
    {
        $errorLog = new ErrorLog(123, 'Test error message');

        $this->assertEquals(123, $errorLog->getImageId());
        $this->assertEquals('Test error message', $errorLog->getErrorMessage());
        $this->assertNull($errorLog->getId());
        $this->assertFalse($errorLog->isPersisted());
    }

    /**
     * Test creating ErrorLog with ImageId value object
     */
    public function testConstructorWithImageIdValueObject(): void
    {
        $imageId = ImageId::fromInt(456);
        $errorLog = new ErrorLog($imageId, 'Another error message');

        $this->assertEquals(456, $errorLog->getImageId());
        $this->assertInstanceOf(ImageId::class, $errorLog->getImageIdObject());
        $this->assertTrue($imageId->equals($errorLog->getImageIdObject()));
    }

    /**
     * Test creating ErrorLog with custom timestamp
     */
    public function testConstructorWithCustomTimestamp(): void
    {
        $timestamp = new DateTimeImmutable('2024-01-15 10:30:00');
        $errorLog = new ErrorLog(123, 'Error message', $timestamp);

        $this->assertEquals($timestamp, $errorLog->getOccurredAt());
        $this->assertEquals('2024-01-15 10:30:00', $errorLog->getFormattedTime());
    }

    /**
     * Test creating ErrorLog with ID (persisted)
     */
    public function testConstructorWithId(): void
    {
        $errorLog = new ErrorLog(123, 'Error message', null, 999);

        $this->assertEquals(999, $errorLog->getId());
        $this->assertTrue($errorLog->isPersisted());
    }

    /**
     * Test error message is trimmed
     */
    public function testErrorMessageIsTrimmed(): void
    {
        $errorLog = new ErrorLog(123, '  Trimmed message  ');

        $this->assertEquals('Trimmed message', $errorLog->getErrorMessage());
    }

    /**
     * Test constructor throws exception for empty error message
     */
    public function testConstructorThrowsExceptionForEmptyMessage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Error message cannot be empty');

        new ErrorLog(123, '');
    }

    /**
     * Test constructor throws exception for whitespace-only error message
     */
    public function testConstructorThrowsExceptionForWhitespaceOnlyMessage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Error message cannot be empty');

        new ErrorLog(123, '   ');
    }

    /**
     * Test constructor throws exception for invalid imageId type
     */
    public function testConstructorThrowsExceptionForInvalidImageIdType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Image ID must be an integer or ImageId instance');

        new ErrorLog('invalid', 'Error message');
    }

    /**
     * Test constructor uses current time if timestamp not provided
     */
    public function testConstructorUsesCurrentTimeIfNotProvided(): void
    {
        $before = new DateTimeImmutable();
        $errorLog = new ErrorLog(123, 'Error message');
        $after = new DateTimeImmutable();

        $occurredAt = $errorLog->getOccurredAt();

        $this->assertGreaterThanOrEqual($before->getTimestamp(), $occurredAt->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $occurredAt->getTimestamp());
    }

    /**
     * Test fromDatabaseRow creates ErrorLog correctly
     */
    public function testFromDatabaseRowCreatesErrorLog(): void
    {
        $row = [
            'id' => 42,
            'image_id' => 123,
            'error_message' => 'Database error message',
            'time' => '2024-01-15 10:30:00',
        ];

        $errorLog = ErrorLog::fromDatabaseRow($row);

        $this->assertEquals(42, $errorLog->getId());
        $this->assertEquals(123, $errorLog->getImageId());
        $this->assertEquals('Database error message', $errorLog->getErrorMessage());
        $this->assertEquals('2024-01-15 10:30:00', $errorLog->getFormattedTime());
        $this->assertTrue($errorLog->isPersisted());
    }

    /**
     * Test fromDatabaseRow without id
     */
    public function testFromDatabaseRowWithoutId(): void
    {
        $row = [
            'image_id' => 123,
            'error_message' => 'Error without ID',
            'time' => '2024-01-15 10:30:00',
        ];

        $errorLog = ErrorLog::fromDatabaseRow($row);

        $this->assertNull($errorLog->getId());
        $this->assertFalse($errorLog->isPersisted());
    }

    /**
     * Test fromDatabaseRow throws exception for missing image_id
     */
    public function testFromDatabaseRowThrowsExceptionForMissingImageId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required fields in database row');

        ErrorLog::fromDatabaseRow([
            'error_message' => 'Error',
            'time' => '2024-01-15 10:30:00',
        ]);
    }

    /**
     * Test fromDatabaseRow throws exception for missing error_message
     */
    public function testFromDatabaseRowThrowsExceptionForMissingErrorMessage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required fields in database row');

        ErrorLog::fromDatabaseRow([
            'image_id' => 123,
            'time' => '2024-01-15 10:30:00',
        ]);
    }

    /**
     * Test fromDatabaseRow throws exception for missing time
     */
    public function testFromDatabaseRowThrowsExceptionForMissingTime(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required fields in database row');

        ErrorLog::fromDatabaseRow([
            'image_id' => 123,
            'error_message' => 'Error',
        ]);
    }

    /**
     * Test getFormattedTime returns correct format
     */
    public function testGetFormattedTimeReturnsCorrectFormat(): void
    {
        $timestamp = new DateTimeImmutable('2024-06-20 14:25:30');
        $errorLog = new ErrorLog(123, 'Error', $timestamp);

        $this->assertEquals('2024-06-20 14:25:30', $errorLog->getFormattedTime());
    }

    /**
     * Test getImageIdObject returns ImageId instance
     */
    public function testGetImageIdObjectReturnsImageIdInstance(): void
    {
        $errorLog = new ErrorLog(123, 'Error message');

        $imageIdObject = $errorLog->getImageIdObject();

        $this->assertInstanceOf(ImageId::class, $imageIdObject);
        $this->assertEquals(123, $imageIdObject->toInt());
    }

    /**
     * Test isPersisted returns false for new records
     */
    public function testIsPersistedReturnsFalseForNewRecords(): void
    {
        $errorLog = new ErrorLog(123, 'Error message');

        $this->assertFalse($errorLog->isPersisted());
    }

    /**
     * Test isPersisted returns true for records with ID
     */
    public function testIsPersistedReturnsTrueForRecordsWithId(): void
    {
        $errorLog = new ErrorLog(123, 'Error message', null, 1);

        $this->assertTrue($errorLog->isPersisted());
    }
}
