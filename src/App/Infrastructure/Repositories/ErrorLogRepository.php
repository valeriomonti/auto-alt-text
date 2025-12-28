<?php

namespace AATXT\App\Infrastructure\Repositories;

use AATXT\App\Domain\Entities\ErrorLog;
use AATXT\App\Infrastructure\Database\ErrorLogSchema;

/**
 * ErrorLog Repository
 *
 * Concrete implementation of ErrorLogRepositoryInterface.
 * Handles persistence and retrieval of ErrorLog entities using WordPress database.
 */
final class ErrorLogRepository implements ErrorLogRepositoryInterface
{
    /**
     * WordPress database abstraction object
     *
     * @var \wpdb
     */
    private $wpdb;

    /**
     * Database schema manager
     *
     * @var ErrorLogSchema
     */
    private $schema;

    /**
     * Constructor
     *
     * @param \wpdb $wpdb WordPress database object
     * @param ErrorLogSchema $schema Database schema manager
     */
    public function __construct($wpdb, ErrorLogSchema $schema)
    {
        $this->wpdb = $wpdb;
        $this->schema = $schema;
    }

    /**
     * Save an error log to the database
     *
     * Creates the table if it doesn't exist, then inserts a new error log record.
     * Note: Currently only supports INSERT, not UPDATE.
     *
     * @param ErrorLog $log The error log entity to save
     * @return void
     */
    public function save(ErrorLog $log): void
    {
        // Ensure table exists
        if (!$this->schema->exists()) {
            $this->schema->create();
        }

        $tableName = $this->schema->getTableName();

        // For now, we only support INSERT (new records)
        // In future, could check if $log->isPersisted() and do UPDATE
        $this->wpdb->insert(
            $tableName,
            [
                'time' => $log->getFormattedTime(),
                'image_id' => $log->getImageId(),
                'error_message' => sanitize_text_field($log->getErrorMessage())
            ],
            ['%s', '%d', '%s']
        );
    }

    /**
     * Find all error logs
     *
     * @param int $limit Maximum number of records to retrieve
     * @return ErrorLog[] Array of ErrorLog entities
     */
    public function findAll(int $limit = 100): array
    {
        if (!$this->schema->exists()) {
            return [];
        }

        $tableName = $this->schema->getTableName();
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$tableName} ORDER BY time DESC LIMIT %d",
            $limit
        );

        $rows = $this->wpdb->get_results($query, ARRAY_A);

        if (empty($rows)) {
            return [];
        }

        return array_map(function($row) {
            return ErrorLog::fromDatabaseRow($row);
        }, $rows);
    }

    /**
     * Find an error log by ID
     *
     * @param int $id The error log ID
     * @return ErrorLog|null The ErrorLog entity if found, null otherwise
     */
    public function findById(int $id): ?ErrorLog
    {
        if (!$this->schema->exists()) {
            return null;
        }

        $tableName = $this->schema->getTableName();
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$tableName} WHERE id = %d",
            $id
        );

        $row = $this->wpdb->get_row($query, ARRAY_A);

        if (empty($row)) {
            return null;
        }

        return ErrorLog::fromDatabaseRow($row);
    }

    /**
     * Delete an error log by ID
     *
     * @param int $id The error log ID to delete
     * @return bool True if deleted successfully, false otherwise
     */
    public function delete(int $id): bool
    {
        if (!$this->schema->exists()) {
            return false;
        }

        $tableName = $this->schema->getTableName();
        $result = $this->wpdb->delete(
            $tableName,
            ['id' => $id],
            ['%d']
        );

        return $result !== false && $result > 0;
    }

    /**
     * Delete all error logs
     *
     * @return int The number of records deleted
     */
    public function deleteAll(): int
    {
        if (!$this->schema->exists()) {
            return 0;
        }

        $tableName = $this->schema->getTableName();
        $query = "DELETE FROM {$tableName}";

        $result = $this->wpdb->query($query);

        return $result !== false ? (int) $result : 0;
    }
}
