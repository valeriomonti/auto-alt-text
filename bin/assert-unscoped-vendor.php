<?php
/**
 * Refuses to let Strauss run when the packages it should scope are missing
 * from vendor/.
 *
 * Strauss runs with `delete_vendor_packages: true`, so after one run the
 * un-prefixed copies are gone. A second run without a fresh `composer install`
 * in between finds nothing to scope and silently writes an empty autoloader
 * into vendor-prefixed/, which makes the plugin fatal. This script runs first
 * in the `strauss` Composer script and exits non-zero in that situation.
 *
 * Usage: php bin/assert-unscoped-vendor.php
 */

$root = dirname(__DIR__);
$lock = $root . '/composer.lock';

if (!is_dir($root . '/vendor/composer')) {
    fwrite(STDERR, "strauss: vendor/ is missing. Run `composer install` first.\n");
    exit(1);
}

// Strauss also drops the packages it deletes from vendor/composer/installed.json,
// so the lock file is the only reliable list of what should be in vendor/.
$data = is_file($lock) ? json_decode((string) file_get_contents($lock), true) : null;
if (!is_array($data) || !isset($data['packages']) || !is_array($data['packages'])) {
    fwrite(STDERR, "strauss: cannot read composer.lock. Run `composer install` first.\n");
    exit(1);
}

$missing = [];
foreach ($data['packages'] as $package) {
    // Non-dev packages only: those are the ones Strauss scopes and deletes.
    if (!is_dir($root . '/vendor/' . $package['name'])) {
        $missing[] = $package['name'];
    }
}

if ($missing !== []) {
    fwrite(
        STDERR,
        "strauss: these packages were already scoped and removed from vendor/: "
        . implode(', ', $missing) . ".\n"
        . "Running Strauss again would produce an empty autoloader.\n"
        . "`composer install` already runs Strauss; if you need to rebuild, run:\n"
        . "  rm -rf vendor vendor-prefixed && composer install\n"
    );
    exit(1);
}
