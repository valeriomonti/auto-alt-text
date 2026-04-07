<?php

declare(strict_types=1);

namespace AATXT\App\Infrastructure\Repositories;

/**
 * Resolves plugin configuration from PHP constants defined in wp-config.php.
 *
 * Maps option keys to their PHP constant equivalents, allowing site owners
 * to hard-code configuration values at the infrastructure level rather than
 * storing them in the database. Constants take precedence over database values
 * when defined.
 *
 * Example wp-config.php usage:
 *   define('AATXT_API_KEY_OPENAI', 'sk-...');
 *   define('AATXT_TYPOLOGY', 'openai');
 */
final class ConstantsResolver
{
    /**
     * Map of WordPress option key => PHP constant name.
     *
     * @var array<string, string>
     */
    private const OPTION_CONSTANT_MAP = [
        'aatxt_typology'                           => 'AATXT_TYPOLOGY',
        'aatxt_api_key_openai'                     => 'AATXT_API_KEY_OPENAI',
        'aatxt_model_openai'                       => 'AATXT_MODEL_OPENAI',
        'aatxt_prompt_openai'                      => 'AATXT_PROMPT_OPENAI',
        'aatxt_api_key_anthropic'                  => 'AATXT_API_KEY_ANTHROPIC',
        'aatxt_model_anthropic'                    => 'AATXT_MODEL_ANTHROPIC',
        'aatxt_prompt_anthropic'                   => 'AATXT_PROMPT_ANTHROPIC',
        'aatxt_api_key_azure_computer_vision'      => 'AATXT_API_KEY_AZURE_COMPUTER_VISION',
        'aatxt_endpoint-azure-computer-vision'     => 'AATXT_ENDPOINT_AZURE_COMPUTER_VISION',
        'aatxt_api_key_azure_translate_instance'   => 'AATXT_API_KEY_AZURE_TRANSLATE_INSTANCE',
        'aatxt_endpoint-azure-translate-instance'  => 'AATXT_ENDPOINT_AZURE_TRANSLATE_INSTANCE',
        'aatxt_region_azure_translate_instance'    => 'AATXT_REGION_AZURE_TRANSLATE_INSTANCE',
        'aatxt_language_azure_translate_instance'  => 'AATXT_LANGUAGE_AZURE_TRANSLATE_INSTANCE',
        'aatxt_preserve_existing_alt_text'         => 'AATXT_PRESERVE_EXISTING_ALT_TEXT',
    ];

    /**
     * Check whether a PHP constant is defined for the given option key.
     *
     * @param string $optionKey WordPress option name
     * @return bool
     */
    public static function has(string $optionKey): bool
    {
        $constantName = self::OPTION_CONSTANT_MAP[$optionKey] ?? null;

        return $constantName !== null && defined($constantName);
    }

    /**
     * Get the value from the PHP constant for the given option key.
     * Returns null if no constant is defined for this key.
     *
     * @param string $optionKey WordPress option name
     * @return string|null
     */
    public static function get(string $optionKey): ?string
    {
        $constantName = self::OPTION_CONSTANT_MAP[$optionKey] ?? null;

        if ($constantName === null || ! defined($constantName)) {
            return null;
        }

        return (string) constant($constantName);
    }

    /**
     * Get the PHP constant name mapped to the given option key.
     * Returns null if no constant is mapped.
     *
     * @param string $optionKey WordPress option name
     * @return string|null
     */
    public static function getConstantName(string $optionKey): ?string
    {
        return self::OPTION_CONSTANT_MAP[$optionKey] ?? null;
    }
}
