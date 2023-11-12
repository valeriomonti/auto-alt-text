<?php

namespace ValerioMonti\AutoAltText\Config;

class Constants
{
    const AAT_PLUGIN_SLUG = 'auto-alt-text';
    const AAT_AZURE_DEFAULT_LANGUAGE = 'en';
    const AAT_PLUGIN_OPTIONS_PAGE_SLUG = 'auto-alt-text-options';
    const AAT_PLUGIN_OPTION_LOG_PAGE_SLUG = 'auto-alt-text-log';
    const AAT_OPTION_FIELD_TYPOLOGY = 'aat_typology';
    const AAT_OPTION_FIELD_PROMPT_OPENAI = 'aat_prompt_openai';
    const AAT_OPTION_FIELD_FALLBACK_PROMPT_OPENAI = 'aat_fallback_prompt_openai';
    const AAT_OPTION_FIELD_FALLBACK_MODEL_OPENAI = 'aat_model_openai';
    const AAT_OPTION_FIELD_API_KEY_OPENAI = 'aat_api_key_openai';
    const AAT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION = 'aat_api_key_azure_computer_vision';
    const AAT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE= 'aat_api_key_azure_translate_instance';
    const AAT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE = 'article-title';
    const AAT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE = 'attachment-title';
    const AAT_OPTION_TYPOLOGY_CHOICE_OPENAI = 'openai';
    const AAT_OPTION_TYPOLOGY_CHOICE_AZURE = 'azure';
    const AAT_OPTION_TYPOLOGY_DEACTIVATED = 'deactivated';
    const AAT_OPTION_FIELD_ENDPOINT_AZURE_COMPUTER_VISION = 'aat_endpoint-azure-computer-vision';
    const AAT_OPTION_FIELD_ENDPOINT_AZURE_TRANSLATE_INSTANCE = 'aat_endpoint-azure-translate-instance';
    const AAT_OPTION_FIELD_REGION_AZURE_TRANSLATE_INSTANCE = 'aat-region-azure-translate-instance';
    const AAT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE = 'aat-language-azure-translate-instance';
    const AAT_IMAGE_URL_TAG = '%imageUrl%';
    const AAT_OPENAI_DEFAULT_PROMPT = "Act like an SEO expert and write an English alt text of up to 125 characters for this image.";
    const AAT_OPENAI_DEFAULT_FALLBACK_PROMPT = "Act like an SEO expert and write an English alt text for an image whit this url %imageUrl%, using a maximum of 125 characters. Just return the text without any additional comments.";
    const AAT_OPENAI_DEFAULT_MODEL = "gpt-3.5-turbo";
    const AAT_OPENAI_MODELS = [
        "gpt-4",
        "gpt-3.5-turbo",
    ];

    const AAT_OPENAI_VISION_MODEL = 'gpt-4-vision-preview';

    const AAT_OPENAI_MAX_TOKENS = 70;
    const AAT_OPENAI_TEXT_COMPLETION_TEMPERATURE = 0.6;

    const AAT_OPENAI_CHAT_COMPLETION_ENDPOINT = 'https://api.openai.com/v1/chat/completions';
    const AAT_OPENAI_TEXT_COMPLETION_ENDPOINT = 'https://api.openai.com/v1/completions';

    const AAT_LOG_ASH = 'aat_log_ash';
    const AAT_LOG_RETENTION_DAYS = 7;
    const AAT_LOGS_CLEANUP_EVENT = 'aat_logs_cleanup_event';
}