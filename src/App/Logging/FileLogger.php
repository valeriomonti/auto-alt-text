<?php
namespace ValerioMonti\AutoAltText\App\Logging;

use OpenAI\Exceptions\ErrorException;
use ValerioMonti\AutoAltText\Config\Constants;

class FileLogger implements LoggerInterface
{
    public function writeImageLog(int $imageId, ErrorException $e): void
    {
        $errorMessage = "[" . date('Y-m-d h:i:s') . "][ERROR][IMAGE_ID:" . $imageId . "] " . $e->getErrorType() . " - " . $e->getErrorCode() . "\n";
        $uploadDir = wp_upload_dir();
        $logDir = trailingslashit($uploadDir['basedir']) . Constants::AAT_PLUGIN_SLUG;
        if(! file_exists($logDir)) {
            wp_mkdir_p($logDir);
        }
        $logFile = trailingslashit($logDir) . date('Y-m-d') . '.log';
        error_log($errorMessage, 3, $logFile);
    }
}