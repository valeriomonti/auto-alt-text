<?php

namespace AATXT\App\Logging;

use AATXT\App\Domain\Entities\ErrorLog;
use AATXT\App\Infrastructure\Database\ErrorLogSchema;
use AATXT\App\Infrastructure\Repositories\ErrorLogRepositoryInterface;

/**
 * Database Logger
 *
 * Logs error messages to the database using the ErrorLogRepository.
 * This class has been refactored to use dependency injection and the repository pattern.
 */
class DBLogger implements LoggerInterface
{
    /**
     * Error log repository
     *
     * @var ErrorLogRepositoryInterface
     */
    private $repository;

    /**
     * Database schema manager
     *
     * @var ErrorLogSchema
     */
    private $schema;

    /**
     * Constructor
     *
     * @param ErrorLogRepositoryInterface $repository Error log repository
     * @param ErrorLogSchema $schema Database schema manager
     */
    public function __construct(ErrorLogRepositoryInterface $repository, ErrorLogSchema $schema)
    {
        $this->repository = $repository;
        $this->schema = $schema;
    }

    /**
     * Create DBLogger instance (static factory method)
     *
     * @deprecated 2.6.0 Use dependency injection instead. This method will be removed in v3.0.0.
     * @return DBLogger
     */
    public static function make(): DBLogger
    {
        _deprecated_function(__METHOD__, '2.6.0', 'Dependency injection via Container');

        global $wpdb;

        // Create dependencies manually for backward compatibility
        $schema = new ErrorLogSchema($wpdb);
        $repository = new \AATXT\App\Infrastructure\Repositories\ErrorLogRepository($wpdb, $schema);

        return new self($repository, $schema);
    }

    /**
     * Write a new record for the single error
     *
     * @param int $imageId WordPress attachment ID
     * @param string $errorMessage Error message text
     * @return void
     */
    public function writeImageLog(int $imageId, string $errorMessage): void
    {
        $errorLog = new ErrorLog($imageId, $errorMessage);
        $this->repository->save($errorLog);
    }

    /**
     * Get all error records from the logs table
     *
     * Returns a formatted string with all error logs.
     *
     * @return string Formatted error logs
     */
    public function getImageLog(): string
    {
        $logs = $this->repository->findAll();

        if (empty($logs)) {
            return "";
        }

        $output = "";
        foreach ($logs as $log) {
            $output .= sprintf(
                "[%s] - Image ID: %d - Error: %s\n",
                $log->getOccurredAt()->format('Y-m-d H:i:s'),
                $log->getImageId(),
                $log->getErrorMessage()
            );
        }

        return $output;
    }

    /**
     * Create the Log table
     *
     * @deprecated 2.6.0 Use ErrorLogSchema::create() instead. This method will be removed in v3.0.0.
     * @return void
     */
    public function createLogTable(): void
    {
        _deprecated_function(__METHOD__, '2.6.0', 'ErrorLogSchema::create()');
        $this->schema->create();
    }

    /**
     * Drop the Log table
     *
     * @deprecated 2.6.0 Use ErrorLogSchema::drop() instead. This method will be removed in v3.0.0.
     * @return void
     */
    public function dropLogTable(): void
    {
        _deprecated_function(__METHOD__, '2.6.0', 'ErrorLogSchema::drop()');
        $this->schema->drop();
    }

    /**
     * Check if Log table exists
     *
     * @deprecated 2.6.0 Use ErrorLogSchema::exists() instead. This method will be removed in v3.0.0.
     * @return bool
     */
    private function logTableExists(): bool
    {
        _deprecated_function(__METHOD__, '2.6.0', 'ErrorLogSchema::exists()');
        return $this->schema->exists();
    }
}
