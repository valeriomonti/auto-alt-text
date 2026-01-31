<?php

namespace AATXT\Config;

class Constants
{
    const AATXT_PLUGIN_SLUG = 'auto-alt-text';
    const AATXT_AZURE_DEFAULT_LANGUAGE = 'en';
    const AATXT_AZURE_COMPUTER_VISION_API_VERSION = '2024-02-01';
    const AATXT_PLUGIN_OPTIONS_PAGE_SLUG = 'auto-alt-text-options';
    const AATXT_PLUGIN_OPTION_LOG_PAGE_SLUG = 'auto-alt-text-log';
    const AATXT_PLUGIN_ASSETS_HANDLE = 'aatxt-auto-alt-text-options';
    const AATXT_PLUGIN_MEDIA_LIBRARY_HANDLE = 'aatxt-auto-alt-text-media-library';
    const AATXT_OPTION_FIELD_TYPOLOGY = 'aatxt_typology';
    const AATXT_OPTION_FIELD_MODEL_OPENAI = 'aatxt_model_openai';

    const AATXT_GPT4O = 'gpt-4o';
    const AATXT_GPT4O_MINI = 'gpt-4o-mini';
    const AATXT_GPT5 = 'gpt-5';
    const AATXT_GPT5_MINI = 'gpt-5-mini';
    const AATXT_GPT5_NANO = 'gpt-5-nano';
    const AATXT_OPTION_FIELD_MODEL_OPENAI_OPTIONS = [
        self::AATXT_GPT4O => 'GPT-4o',
        self::AATXT_GPT4O_MINI => 'GPT-4o Mini',
        self::AATXT_GPT5 => 'GPT-5',
        self::AATXT_GPT5_MINI => 'GPT-5 Mini',
        self::AATXT_GPT5_NANO => 'GPT-5 Nano',
    ];

    const AATXT_OPTION_FIELD_PROMPT_OPENAI = 'aatxt_prompt_openai';
    const AATXT_OPTION_FIELD_API_KEY_OPENAI = 'aatxt_api_key_openai';
    const AATXT_OPTION_FIELD_API_KEY_ANTHROPIC = 'aatxt_api_key_anthropic';
    const AATXT_OPTION_FIELD_MODEL_ANTHROPIC = 'aatxt_model_anthropic';
    const AATXT_CLAUDE_SONNET_4 = 'claude-sonnet-4-20250514';
    const AATXT_CLAUDE_HAIKU_3_5 = 'claude-3-5-haiku-20241022';

    const AATXT_OPTION_FIELD_MODEL_ANTHROPIC_OPTIONS = [
        self::AATXT_CLAUDE_HAIKU_3_5 => 'Claude 3.5 Haiku',
        self::AATXT_CLAUDE_SONNET_4 => 'Claude Sonnet 4',
    ];
    const AATXT_API_VERSION = '2023-06-01';
    const AATXT_OPTION_FIELD_PROMPT_ANTHROPIC = 'aatxt_prompt_anthropic';
    const AATXT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION = 'aatxt_api_key_azure_computer_vision';
    const AATXT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE = 'aatxt_api_key_azure_translate_instance';
    const AATXT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE = 'article-title';
    const AATXT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE = 'attachment-title';
    const AATXT_OPTION_TYPOLOGY_CHOICE_OPENAI = 'openai';
    const AATXT_OPTION_TYPOLOGY_CHOICE_AZURE = 'azure';
    const AATXT_OPTION_TYPOLOGY_CHOICE_ANTHROPIC = 'anthropic';
    const AATXT_OPTION_TYPOLOGY_DEACTIVATED = 'deactivated';
    const AATXT_OPTION_FIELD_ENDPOINT_AZURE_COMPUTER_VISION = 'aatxt_endpoint-azure-computer-vision';
    const AATXT_OPTION_FIELD_ENDPOINT_AZURE_TRANSLATE_INSTANCE = 'aatxt_endpoint-azure-translate-instance';
    const AATXT_OPTION_FIELD_REGION_AZURE_TRANSLATE_INSTANCE = 'aatxt_region_azure_translate_instance';
    const AATXT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE = 'aatxt_language_azure_translate_instance';
    const AATXT_OPTION_FIELD_PRESERVE_EXISTING_ALT_TEXT = 'aatxt_preserve_existing_alt_text';
    const AATXT_LEGACY_ENCRYPTION_MIGRATION_DONE = 'aatxt_legacy_encryption_migration_done';
    const AATXT_IMAGE_URL_TAG = '%imageUrl%';
    const AATXT_OPENAI_DEFAULT_PROMPT = "Act like an SEO expert and write an English alt text of up to 125 characters for this image.";
    const AATXT_OPENAI_RESPONSES_API_ENDPOINT = 'https://api.openai.com/v1/responses';
    const AATXT_AJAX_GENERATE_ALT_TEXT_NONCE = 'generate_alt_text_nonce';
    const AATXT_OPENAI_ALLOWED_MIME_TYPES = [
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/webp',
    ];
    const AATXT_AZURE_ALLOWED_MIME_TYPES = [
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/webp',
    ];

    const AATXT_ANTHROPIC_ALLOWED_MIME_TYPES = [
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/webp',
    ];

    const AATXT_ANTHROPIC_ENDPOINT = 'https://api.anthropic.com/v1/messages';
}