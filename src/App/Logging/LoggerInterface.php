<?php
namespace ValerioMonti\AutoAltText\App\Logging;

use OpenAI\Exceptions\ErrorException;
interface LoggerInterface {
    public function writeImageLog(int $imageId, ErrorException $e): void;
}