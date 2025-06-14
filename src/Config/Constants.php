<?php

namespace AATXT\Config;

class Constants
{
    const AATXT_PLUGIN_SLUG = 'auto-alt-text';
    const AATXT_AZURE_DEFAULT_LANGUAGE = 'en';
    const AATXT_PLUGIN_OPTIONS_PAGE_SLUG = 'auto-alt-text-options';
    const AATXT_PLUGIN_OPTION_LOG_PAGE_SLUG = 'auto-alt-text-log';
    const AATXT_PLUGIN_ASSETS_HANDLE = 'aatxt-auto-alt-text-options';
    const AATXT_PLUGIN_MEDIA_LIBRARY_HANDLE = 'aatxt-auto-alt-text-media-library';
    const AATXT_OPTION_FIELD_TYPOLOGY = 'aatxt_typology';
    const AATXT_OPTION_FIELD_MODEL_OPENAI = 'aatxt_model_openai';

    const AATXT_GPT4O = 'gpt-4o';
    const AATXT_GPT4O_MINI = 'gpt-4o-mini';
    const AATXT_O1_MINI = 'o1-mini';
    const AATXT_OPTION_FIELD_MODEL_OPENAI_OPTIONS = [
        self::AATXT_GPT4O => 'GPT-4o',
        self::AATXT_GPT4O_MINI => 'GPT-4o Mini',
        self::AATXT_O1_MINI => 'o1 Mini',
    ];
    const AATXT_OPTION_FIELD_PROMPT_OPENAI = 'aatxt_prompt_openai';
    const AATXT_OPTION_FIELD_API_KEY_OPENAI = 'aatxt_api_key_openai';
    const AATXT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION = 'aatxt_api_key_azure_computer_vision';
    const AATXT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE = 'aatxt_api_key_azure_translate_instance';
    const AATXT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE = 'article-title';
    const AATXT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE = 'attachment-title';
    const AATXT_OPTION_TYPOLOGY_CHOICE_OPENAI = 'openai';
    const AATXT_OPTION_TYPOLOGY_CHOICE_AZURE = 'azure';
    const AATXT_OPTION_TYPOLOGY_DEACTIVATED = 'deactivated';
    const AATXT_OPTION_FIELD_ENDPOINT_AZURE_COMPUTER_VISION = 'aatxt_endpoint-azure-computer-vision';
    const AATXT_OPTION_FIELD_ENDPOINT_AZURE_TRANSLATE_INSTANCE = 'aatxt_endpoint-azure-translate-instance';
    const AATXT_OPTION_FIELD_REGION_AZURE_TRANSLATE_INSTANCE = 'aatxt_region_azure_translate_instance';
    const AATXT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE = 'aatxt_language_azure_translate_instance';
    const AATXT_OPTION_FIELD_PRESERVE_EXISTING_ALT_TEXT = 'aatxt_preserve_existing_alt_text';
    const AATXT_LEGACY_ENCRYPTION_MIGRATION_DONE = 'aatxt_legacy_encryption_migration_done';
    const AATXT_IMAGE_URL_TAG = '%imageUrl%';
    const AATXT_OPENAI_DEFAULT_PROMPT = "Act like an SEO expert and write an English alt text of up to 125 characters for this image.";
    const AATXT_OPENAI_VISION_MODEL = 'gpt-4o';
    const AATXT_OPENAI_FALLBACK_MODEL = 'gpt-4o-mini';
    const AATXT_OPENAI_MAX_TOKENS = 70;
    const AATXT_OPENAI_CHAT_COMPLETION_ENDPOINT = 'https://api.openai.com/v1/chat/completions';
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
}