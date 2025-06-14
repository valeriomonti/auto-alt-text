<?php

namespace AATXT\App\Utilities;

use AATXT\Config\Constants;
use RuntimeException;

final class Encryption
{
    private string $key;
    private string $salt;

    public function __construct()
    {
        $this->key = $this->getKey();
        $this->salt = $this->getSalt();
    }

    /**
     * @return Encryption
     */
    public static function make(): Encryption
    {
        return new self();
    }

    /**
     * @param string $value
     * @return string|bool
     */
    public function encrypt(string $value): string
    {
        if (empty($value)) {
            return '';
        }

        if (!extension_loaded('openssl')) {
            return $value;
        }

        $method = 'aes-256-ctr';
        $ivLength = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $raw_value = openssl_encrypt($value . $this->salt, $method, $this->key, 0, $iv);
        if (!$raw_value) {
            throw new RuntimeException('Encryption failed.');
        }

        return base64_encode($iv . $raw_value);
    }

    /**
     * Attempts to decrypt a raw value using the provided key and salt.
     **/
    private function attemptDecrypt(string $rawValue, string $key, string $salt): string
    {
        $decoded   = base64_decode($rawValue, true);
        $method    = 'aes-256-ctr';
        $ivLength  = openssl_cipher_iv_length($method);
        $iv        = substr($decoded, 0, $ivLength);
        $cipher    = substr($decoded, $ivLength);
        $decrypted = openssl_decrypt($cipher, $method, $key, 0, $iv);

        // If decryption fails or the salt is not present, return an empty string
        if (! $decrypted || substr($decrypted, -strlen($salt)) !== $salt) {
            return '';
        }
        return substr($decrypted, 0, -strlen($salt));
    }

    /**
     * Decrypts a raw value trying with the plugin key and salt first, then falling back to the logged-in key and salt.
     * @param string $rawValue
     * @return string
     */
    public function decrypt(string $rawValue): string
    {
        if (empty($rawValue) || ! extension_loaded('openssl')) {
            return '';
        }

        try {
            $plain   = $this->attemptDecrypt($rawValue, $this->key, $this->salt);
            if ($plain !== '') {
                return $plain;
            }

            // If the decryption with the plugin key fails, try as fallback with the logged-in key and salt
            if (defined('LOGGED_IN_KEY') && defined('LOGGED_IN_SALT')) {
                $plainOld = $this->attemptDecrypt($rawValue, LOGGED_IN_KEY, LOGGED_IN_SALT);
                if ($plainOld !== '') {
                    return $plainOld;
                }
            }

            return '';
        } catch (\Throwable $e) {
            throw new RuntimeException('Decryption failed.');
        }
    }

    private function getKey(): string
    {
        if ( defined( 'AATXT_ENCRYPTION_KEY' ) && '' !== AATXT_ENCRYPTION_KEY ) {
            return AATXT_ENCRYPTION_KEY;
        }
        // If the constant is not defined, use the WordPress constants LOGGED_IN_KEY and LOGGED_IN_SALT
        if (defined('LOGGED_IN_KEY') && '' !== LOGGED_IN_KEY) {
            return LOGGED_IN_KEY;
        }

        // If this is reached, you're either not on a live site or have a serious security issue.
        return 'warning-not-logged-in-key-constant-defined';
    }

    public function getSalt(): string
    {
        if ( defined( 'AATXT_ENCRYPTION_SALT' ) && '' !== AATXT_ENCRYPTION_SALT ) {
            return AATXT_ENCRYPTION_SALT;
        }
        // If the constant is not defined, use the WordPress constants LOGGED_IN_KEY and LOGGED_IN_SALT
        if (defined('LOGGED_IN_SALT') && '' !== LOGGED_IN_SALT) {
            return LOGGED_IN_SALT;
        }

        // If this is reached, you're either not on a live site or have a serious security issue.
        return 'warning-not-logged-in-salt-constant-defined';
    }

    /**
     * Migrate legacy API keys from the old encryption method to the new one.
     * This is necessary for backward compatibility with older versions of the plugin.
     */
    public function migrateLegacyApiKeys(): void
    {
        $fields = [
            Constants::AATXT_OPTION_FIELD_API_KEY_OPENAI,
            Constants::AATXT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION,
            Constants::AATXT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE,
        ];

        foreach ($fields as $optionName) {
            $raw = get_option($optionName);
            if (empty($raw)) {
                continue;
            }

            if (! defined('LOGGED_IN_KEY') || ! defined('LOGGED_IN_SALT')) {
                continue;
            }
            // Try to decrypt options using the old method with the WordPress constants LOGGED_IN_KEY and LOGGED_IN_SALT
            $plain = $this->attemptDecrypt($raw, LOGGED_IN_KEY, LOGGED_IN_SALT);

            if ($plain === '') {
                // Decryption failed, cause the option is encrypted with the new method or the old method failed
                continue;
            }

            //Resave the option as plain because the action "encryptDataOnUpdate" will encrypt before saving in the database
            update_option($optionName, $plain);
        }
    }
}
