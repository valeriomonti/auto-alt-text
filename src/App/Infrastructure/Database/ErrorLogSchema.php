<?php

namespace AATXT\App\Infrastructure\Database;

/**
 * Manages the database schema for error logs table
 *
 * This class is responsible for creating, dropping, and checking the existence
 * of the error logs table in the WordPress database.
 */
final class ErrorLogSchema
{
    /**
     * WordPress database abstraction object
     *
     * @var \wpdb
     */
    private $wpdb;

    /**
     * Table name without prefix
     *
     * @var string
     */
    private $tableName;

    /**
     * Constructor
     *
     * @param \wpdb $wpdb WordPress database object
     */
    public function __construct(\wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
        $this->tableName = 'aatxt_logs';
    }

    /**
     * Create the error logs table
     *
     * Creates the table if it doesn't exist using dbDelta for safe schema updates.
     *
     * @return void
     */
    public function create(): void
    {
        if ($this->exists()) {
            return;
        }

        $charset_collate = $this->wpdb->get_charset_collate();
        $tableName = $this->getTableName();

        $sql = "CREATE TABLE {$tableName} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            image_id mediumint(9) NOT NULL,
            error_message text NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Drop the error logs table
     *
     * Permanently removes the table from the database.
     *
     * @return void
     */
    public function drop(): void
    {
        $tableName = $this->getTableName();
        $sql = "DROP TABLE IF EXISTS {$tableName};";
        $this->wpdb->query($sql);
    }

    /**
     * Check if the error logs table exists
     *
     * @return bool True if table exists, false otherwise
     */
    public function exists(): bool
    {
        $tableName = $this->getTableName();
        $query = $this->wpdb->prepare("SHOW TABLES LIKE %s", $tableName);

        return $this->wpdb->get_var($query) === $tableName;
    }

    /**
     * Get the full table name with WordPress prefix
     *
     * @return string Full table name including WordPress prefix
     */
    public function getTableName(): string
    {
        return $this->wpdb->prefix . $this->tableName;
    }
}
