<?php

namespace AATXT\App\Logging;

class DBLogger implements LoggerInterface
{
    private function __construct()
    {

    }

    public static function make(): DBLogger
    {
        return new self();
    }

    /**
     * Write a new record for the single error
     * @param int $imageId
     * @param string $errorMessage
     * @return void
     */
    public function writeImageLog(int $imageId, string $errorMessage): void
    {
        global $wpdb;

        if(!$this->logTableExists()) {
            $this->createLogTable();
        }

        $sanitizedErrorMessage = sanitize_text_field($errorMessage);

        $currentDateTime = current_time('mysql');
        $wpdb->insert(
            $wpdb->prefix . 'aatxt_logs',
            [
                'time' => $currentDateTime,
                'image_id' => $imageId,
                'error_message' => $sanitizedErrorMessage
            ],
            ['%s', '%d', '%s']
        );
    }

    /**
     * Get all error records from the logs table
     * @return string
     */
    public function getImageLog(): string
    {
        global $wpdb;
        $output = "";

        if(!$this->logTableExists()) {
            return $output;
        }

        $query = "SELECT * FROM {$wpdb->prefix}aatxt_logs ORDER BY time DESC";
        $logs = $wpdb->get_results($query, ARRAY_A);

        if(empty($logs)) {
            return $output;
        }

        foreach ($logs as $log) {
            $output .= sprintf("[%s] - Image ID: %d - Error: %s\n",
                $log['time'], $log['image_id'], $log['error_message']);
        }

        return $output;
    }

    /**
     * Check if Log table exists
     * @return bool
     */
    private function logTableExists(): bool
    {
        global $wpdb;
        $tableCheckQuery = $wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix . 'aatxt_logs');

        return $wpdb->get_var($tableCheckQuery) == $wpdb->prefix . 'aatxt_logs';
    }

    /**
     * Create the Log table
     * @return void
     */
    public function createLogTable(): void
    {
        global $wpdb;
        $tableCheckQuery = $wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix . 'aatxt_logs');

        if ($wpdb->get_var($tableCheckQuery) != $wpdb->prefix . 'aatxt_logs') {
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE {$wpdb->prefix}aatxt_logs (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                image_id mediumint(9) NOT NULL,
                error_message text NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    /**
     * Dropt the Log table
     * @return void
     */
    public function dropLogTable(): void
    {
        global $wpdb;
        $sql = "DROP TABLE IF EXISTS {$wpdb->prefix}aatxt_logs;";
        $wpdb->query($sql);
    }

}