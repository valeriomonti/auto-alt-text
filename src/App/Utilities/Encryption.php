<?php

namespace AATXT\App\Utilities;

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
     * @param string $rawValue
     * @return string|bool
     */
    public function decrypt(string $rawValue): string
    {
        if (empty($rawValue) || ! extension_loaded('openssl')) {
            return '';
        }

        try {
            $decoded = base64_decode($rawValue, true);
            $method = 'aes-256-ctr';
            $ivLength = openssl_cipher_iv_length($method);
            $iv = substr($decoded, 0, $ivLength);
            $cipherText = substr($decoded, $ivLength);
            $decrypted = openssl_decrypt($cipherText, $method, $this->key, 0, $iv);

            // If decryption fails or the salt is not present, return an empty string
            if ( ! $decrypted || substr($decrypted, -strlen($this->salt)) !== $this->salt) {
                return '';
            }

            return substr($decrypted, 0, -strlen($this->salt));
        } catch (\Throwable $e) {
            throw new RuntimeException('Decryption failed.');
        }
    }


    /**
     * Get key from WordPress Authentication Unique Keys and Salts
     */
    private function getKey(): string
    {
        if (defined('LOGGED_IN_KEY') && '' !== LOGGED_IN_KEY) {
            return LOGGED_IN_KEY;
        }

        // If this is reached, you're either not on a live site or have a serious security issue.
        return 'warning-not-logged-in-key-constant-defined';
    }

    /**
     * Get salt from WordPress Authentication Unique Keys and Salts
     */
    public function getSalt(): string
    {
        if (defined('LOGGED_IN_SALT') && '' !== LOGGED_IN_SALT) {
            return LOGGED_IN_SALT;
        }

        // If this is reached, you're either not on a live site or have a serious security issue.
        return 'warning-not-logged-in-salt-constant-defined';
    }
}
