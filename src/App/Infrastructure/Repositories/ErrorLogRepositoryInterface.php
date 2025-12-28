<?php

namespace AATXT\App\Infrastructure\Repositories;

use AATXT\App\Domain\Entities\ErrorLog;

/**
 * ErrorLog Repository Interface
 *
 * Defines the contract for persisting and retrieving ErrorLog entities.
 * This interface follows the Repository Pattern to abstract data access.
 */
interface ErrorLogRepositoryInterface
{
    /**
     * Save an error log to the database
     *
     * If the ErrorLog has an ID (is persisted), this should update the existing record.
     * If the ErrorLog has no ID (is not persisted), this should create a new record.
     *
     * @param ErrorLog $log The error log entity to save
     * @return void
     */
    public function save(ErrorLog $log): void;

    /**
     * Find all error logs
     *
     * Returns all error logs ordered by time descending (most recent first).
     *
     * @param int $limit Maximum number of records to retrieve (default: 100)
     * @return ErrorLog[] Array of ErrorLog entities
     */
    public function findAll(int $limit = 100): array;

    /**
     * Find an error log by ID
     *
     * @param int $id The error log ID
     * @return ErrorLog|null The ErrorLog entity if found, null otherwise
     */
    public function findById(int $id): ?ErrorLog;

    /**
     * Delete an error log by ID
     *
     * @param int $id The error log ID to delete
     * @return bool True if deleted successfully, false otherwise
     */
    public function delete(int $id): bool;

    /**
     * Delete all error logs
     *
     * Removes all error log records from the database.
     *
     * @return int The number of records deleted
     */
    public function deleteAll(): int;
}
