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
        $output = "";

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

}