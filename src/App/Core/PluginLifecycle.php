<?php

namespace AATXT\App\Core;

use AATXT\App\Infrastructure\Database\ErrorLogSchema;

/**
 * Handles plugin lifecycle events (activation/deactivation).
 *
 * This class follows the Single Responsibility Principle by handling
 * only lifecycle-related operations like database schema management.
 */
final class PluginLifecycle
{
    /**
     * Database schema manager for error logs table
     *
     * @var ErrorLogSchema
     */
    private $schema;

    /**
     * Constructor
     *
     * @param ErrorLogSchema $schema Database schema manager
     */
    public function __construct(ErrorLogSchema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * Handle plugin activation.
     *
     * Creates necessary database tables.
     *
     * @return void
     */
    public function activate(): void
    {
        $this->schema->create();
    }

    /**
     * Handle plugin deactivation.
     *
     * Drops database tables created by the plugin.
     *
     * @return void
     */
    public function deactivate(): void
    {
        $this->schema->drop();
    }
}
