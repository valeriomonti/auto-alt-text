<?php

namespace ValerioMonti\AutoAltText\App\Logging;

use Exception;
use ValerioMonti\AutoAltText\App\Utilities\Encryption;
use ValerioMonti\AutoAltText\Config\Constants;

class FileLogger implements LoggerInterface
{
    private Encryption $encryption;

    private function __construct(Encryption $encryption)
    {
        $this->encryption = $encryption;
    }

    public static function make(Encryption $encryption): FileLogger
    {
        return new self($encryption);
    }

    /**
     * If not exists create the daily error log file and append in it a new line about the current error
     * @param int $imageId
     * @param string $errorMessage
     * @return void
     */
    public function writeImageLog(int $imageId, string $errorMessage): void
    {
        $salt = $this->encryption->getSalt();

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

    /**
     * Find the last created log file in the log directory
     * @param string $logDir
     * @return string
     */
    public function findLatestLogFile(string $logDir): string
    {
        $latest_time = 0;
        $latest_file = null;

        $files = glob(trailingslashit($logDir) . '*.log');
        foreach ($files as $file) {
            $file_time = filemtime($file);
            if ($file_time > $latest_time) {
                $latest_time = $file_time;
                $latest_file = $file;
            }
        }

        return $latest_file;
    }
}