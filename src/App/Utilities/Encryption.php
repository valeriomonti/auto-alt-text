<?php

namespace ValerioMonti\AutoAltText\App\Utilities;

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
     * @param string $value
     * @return string|bool
     */
    public function encrypt(string $value): string|bool
    {
        if (!extension_loaded('openssl')) {
            return $value;
        }

        $method = 'aes-256-ctr';
        $ivLength = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $raw_value = openssl_encrypt($value . $this->salt, $method, $this->key, 0, $iv);
        if (!$raw_value) {
            return false;
        }

        return base64_encode($iv . $raw_value);
    }

    /**
     * @param string $rawValue
     * @return string|bool
     */
    public function decrypt(string $rawValue): string|bool
    {
        /** @noinspection DuplicatedCode */
        if (!extension_loaded('openssl')) {
            return $rawValue;
        }

        $rawValue = base64_decode($rawValue, true);

        $method = 'aes-256-ctr';
        $ivLength = openssl_cipher_iv_length($method);
        $iv = substr($rawValue, 0, $ivLength);

        $rawValue = substr($rawValue, $ivLength);

        $value = openssl_decrypt($rawValue, $method, $this->key, 0, $iv);

        if (!$value || !str_ends_with($value, $this->salt)) {
            return false;
        }

        return substr($value, 0, -strlen($this->salt));
    }

    /**
     * @return string
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
     * @return string
     */
    private function getSalt(): string
    {
        if (defined('LOGGED_IN_SALT') && '' !== LOGGED_IN_SALT) {
            return LOGGED_IN_SALT;
        }

        // If this is reached, you're either not on a live site or have a serious security issue.
        return 'warning-not-logged-in-key-constant-defined';
    }
}
