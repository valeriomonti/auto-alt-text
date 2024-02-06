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
        $tableName = $wpdb->prefix . Constants::AATXT_LOG_TABLE_NAME;

        if($wpdb->get_var("SHOW TABLES LIKE '{$tableName}'") != $tableName) {
            return;
        }

        $currentDateTime = current_time('mysql');
        $wpdb->insert(
            $tableName,
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
        $tableName = $wpdb->prefix . Constants::AATXT_LOG_TABLE_NAME;

        $logs = $wpdb->get_results("SELECT * FROM $tableName ORDER BY time DESC", ARRAY_A);

        $output = "";

        foreach ($logs as $log) {
            $output .= sprintf("[%s] - Image ID: %d - Error: %s\n",
                $log['time'], $log['image_id'], $log['error_message']);
        }

        return $output;
    }

}