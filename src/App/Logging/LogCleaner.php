<?php

namespace AATXT\App\Logging;

use AATXT\Config\Constants;

class LogCleaner
{
    private static ?self $instance = null;

    private function __construct()
    {
    }

    /**
     * Schedule the error log cleanup
     * @return void
     */
    public static function register(): void
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        if (!wp_next_scheduled(Constants::AAT_LOGS_CLEANUP_EVENT)) {
            wp_schedule_event(time(), 'daily', Constants::AAT_LOGS_CLEANUP_EVENT);
        }

        add_action(Constants::AAT_LOGS_CLEANUP_EVENT, [self::$instance, 'cleanupOldLogs']);
    }

    /**
     * Remove old log files
     * @return void
     */
    public static function cleanupOldLogs(): void
    {
        $logDir = trailingslashit(wp_upload_dir()['basedir']) . Constants::AAT_PLUGIN_SLUG;

        if (is_dir($logDir)) {
            $files = glob(trailingslashit($logDir) . '*.log');
            $now = time();
            $days = Constants::AAT_LOG_RETENTION_DAYS;

            foreach ($files as $file) {
                if (is_file($file)) {
                    if ($now - filemtime($file) >= $days * 24 * 60 * 60) {
                        unlink($file);
                    }
                }
            }
        }
    }
}