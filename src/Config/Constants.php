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
    const AATXT_OPENAI_DEFAULT_MODEL = "gpt-4-turbo";
    const AATXT_OPENAI_MODELS = [
        "gpt-4-turbo",
        "gpt-4",
    ];
    const AATXT_OPENAI_VISION_MODEL = 'gpt-4o';
    const AATXT_OPENAI_FALLBACK_MODEL = 'gpt-4-turbo';
    const AATXT_OPENAI_MAX_TOKENS = 70;
    const AATXT_OPENAI_CHAT_COMPLETION_ENDPOINT = 'https://api.openai.com/v1/chat/completions';
}