<?php

namespace AATXT\App\Utilities;

final class AssetsManager
{
    public function __construct()
    {
    }

    public static function make(): AssetsManager
    {
        return new self();
    }

    public function getAssetUrl(string $filename, bool $isStyle = false): string
    {
        $manifestPath = AATXT_ABSPATH . '/dist/.vite/manifest.json';

        if (!file_exists($manifestPath)) {
            return '';
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        $entry = $manifest[$filename] ?? null;

        if (!$entry) {
            return '';
        }
        $file = $isStyle && isset($entry['css']) ? $entry['css'][0] : $entry['file'];

        return AATXT_URL . 'dist/' . $file;
    }

}
