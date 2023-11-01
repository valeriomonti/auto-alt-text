<?php
namespace ValerioMonti\AutoAltText\App\Logging;

use Exception;
use ValerioMonti\AutoAltText\App\Utilities\Encryption;
use ValerioMonti\AutoAltText\Config\Constants;

class FileLogger implements LoggerInterface
{
    private function __construct()
    {

    }

    public static function make(): FileLogger
    {
        return new self();
    }

    public function writeImageLog(int $imageId, string $errorMessage): void
    {
        $salt = (new Encryption())->getSalt();

        //generate a hash based on salt and date
        $hash = md5($salt . date('Y-m-d'));

        $savedHash = get_option(Constants::AAT_LOG_ASH);
        if ($savedHash !== $hash) {
            update_option(Constants::AAT_LOG_ASH, $hash);
        }

        $errorMessage = "[" . date('Y-m-d h:i:s') . "][ERROR][IMAGE_ID:" . $imageId . "] " . $errorMessage . "\n";
        $uploadDir = wp_upload_dir();
        $logDir = trailingslashit($uploadDir['basedir']) . Constants::AAT_PLUGIN_SLUG;
        if (!file_exists($logDir)) {
            wp_mkdir_p($logDir);
        }
        $logFile = trailingslashit($logDir) . date('Y-m-d') . '-' . $hash . '.log';
        error_log($errorMessage, 3, $logFile);
    }
}