<?php

namespace ValerioMonti\AutoAltText\App\Logging;

use ValerioMonti\AutoAltText\Config\Constants;

class LogCleaner
{
    private static ?self $instance = null;

    private function __construct()
    {

    }

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

    public static function cleanupOldLogs() {
        $logDir = trailingslashit(wp_upload_dir()['basedir']) . Constants::AAT_PLUGIN_SLUG;

        if (is_dir($logDir)) {
            $files = glob(trailingslashit($logDir) . '*.log');
            $now   = time();
            $days  = Constants::AAT_LOG_RETENTION_DAYS;

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