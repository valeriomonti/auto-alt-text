<?php

namespace AATXT\App\Logging;
use AATXT\Config\Constants;

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
        $tableCheckQuery = $wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix . 'aatxt_logs');

        if($wpdb->get_var($tableCheckQuery) != $wpdb->prefix . 'aatxt_logs') {
            return;
        }

        $currentDateTime = current_time('mysql');
        $wpdb->insert(
            $wpdb->prefix . 'aatxt_logs',
            [
                'time' => $currentDateTime,
                'image_id' => $imageId,
                'error_message' => $errorMessage
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
        $query = $wpdb->prepare("SELECT * FROM %s ORDER BY time DESC", $wpdb->prefix . 'aatxt_logs');
        $logs = $wpdb->get_results($query, ARRAY_A);

        $output = "";

        foreach ($logs as $log) {
            $output .= sprintf("[%s] - Image ID: %d - Error: %s\n",
                $log['time'], $log['image_id'], $log['error_message']);
        }

        return $output;
    }

}