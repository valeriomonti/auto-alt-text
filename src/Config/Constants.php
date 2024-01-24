<?php

namespace AATXT\Config;

class Constants
{
    const AATXT_PLUGIN_SLUG = 'auto-alt-text';
    const AATXT_AZURE_DEFAULT_LANGUAGE = 'en';
    const AATXT_PLUGIN_OPTIONS_PAGE_SLUG = 'auto-alt-text-options';
    const AATXT_PLUGIN_OPTION_LOG_PAGE_SLUG = 'auto-alt-text-log';
    const AATXT_PLUGIN_ASSETS_HANDLE = 'aatxt-auto-alt-text-options';
    const AATXT_OPTION_FIELD_TYPOLOGY = 'aatxt_typology';
    const AATXT_OPTION_FIELD_PROMPT_OPENAI = 'aatxt_prompt_openai';
    const AATXT_OPTION_FIELD_FALLBACK_PROMPT_OPENAI = 'aatxt_fallback_prompt_openai';
    const AATXT_OPTION_FIELD_FALLBACK_MODEL_OPENAI = 'aatxt_model_openai';
    const AATXT_OPTION_FIELD_API_KEY_OPENAI = 'aatxt_api_key_openai';
    const AATXT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION = 'aatxt_api_key_azure_computer_vision';
    const AATXT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE= 'aatxt_api_key_azure_translate_instance';
    const AATXT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE = 'article-title';
    const AATXT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE = 'attachment-title';
    const AATXT_OPTION_TYPOLOGY_CHOICE_OPENAI = 'openai';
    const AATXT_OPTION_TYPOLOGY_CHOICE_AZURE = 'azure';
    const AATXT_OPTION_TYPOLOGY_DEACTIVATED = 'deactivated';
    const AATXT_OPTION_FIELD_ENDPOINT_AZURE_COMPUTER_VISION = 'aatxt_endpoint-azure-computer-vision';
    const AATXT_OPTION_FIELD_ENDPOINT_AZURE_TRANSLATE_INSTANCE = 'aatxt_endpoint-azure-translate-instance';
    const AATXT_OPTION_FIELD_REGION_AZURE_TRANSLATE_INSTANCE = 'aatxt_region_azure_translate_instance';
    const AATXT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE = 'aatxt_language_azure_translate_instance';
    const AATXT_IMAGE_URL_TAG = '%imageUrl%';
    const AATXT_OPENAI_DEFAULT_PROMPT = "Act like an SEO expert and write an English alt text of up to 125 characters for this image.";
    const AATXT_OPENAI_DEFAULT_FALLBACK_PROMPT = "Act like an SEO expert and write an English alt text for an image whit this url %imageUrl%, using a maximum of 125 characters. Just return the text without any additional comments.";
    const AATXT_OPENAI_DEFAULT_MODEL = "gpt-3.5-turbo";
    const AATXT_OPENAI_MODELS = [
        "gpt-4",
        "gpt-3.5-turbo",
    ];
    const AATXT_OPENAI_VISION_MODEL = 'gpt-4-vision-preview';
    const AATXT_OPENAI_MAX_TOKENS = 70;
    const AATXT_OPENAI_TEXT_COMPLETION_TEMPERATURE = 0.6;
    const AATXT_OPENAI_CHAT_COMPLETION_ENDPOINT = 'https://api.openai.com/v1/chat/completions';
    const AATXT_OPENAI_TEXT_COMPLETION_ENDPOINT = 'https://api.openai.com/v1/completions';
    const AATXT_LOG_ASH = 'aatxt_log_ash';
    const AATXT_LOG_RETENTION_DAYS = 7;
    const AATXT_LOGS_CLEANUP_EVENT = 'aatxt_logs_cleanup_event';
    const AATXT_LOG_TABLE_NAME = 'aatxt_logs';
}