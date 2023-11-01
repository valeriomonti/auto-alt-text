<?php

namespace ValerioMonti\AutoAltText\Config;

class Constants
{
    const AAT_PLUGIN_SLUG = 'auto-alt-text';
    const AAT_PLUGIN_OPTIONS_PAGE_SLUG= 'auto-alt-text-options';
    const AAT_OPTION_FIELD_TYPOLOGY = 'aat_typology';
    const AAT_OPTION_FIELD_PROMPT_OPENAI = 'aat_prompt_openai';
    const AAT_OPTION_FIELD_MODEL_OPENAI = 'aat_model_openai';
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
    const AAT_ENDPOINT_OPENAI_CHAT_COMPLETION = 'chat-completion';
    const AAT_ENDPOINT_OPENAI_TEXT_COMPLETION = 'text-completion';
    const AAT_IMAGE_URL_TAG = '%imageUrl%';
    const AAT_OPENAI_DEFAULT_PROMPT = "Agisci come un esperto SEO e scrivi un alt text in italiano lungo al massimo 15 parole per questa immagine: " . self::AAT_IMAGE_URL_TAG . ". Limitati semplicemente a ritornare il testo senza virgolette.";
    const AAT_OPENAI_DEFAULT_MODEL = "text-davinci-003";
    const AAT_OPENAI_MODELS = [
        "gpt-4" => self::AAT_ENDPOINT_OPENAI_CHAT_COMPLETION,
        "gpt-4-0314" => self::AAT_ENDPOINT_OPENAI_CHAT_COMPLETION,
        "gpt-4-32k" => self::AAT_ENDPOINT_OPENAI_CHAT_COMPLETION,
        "gpt-4-32k-0314" => self::AAT_ENDPOINT_OPENAI_CHAT_COMPLETION,
        "gpt-3.5-turbo" => self::AAT_ENDPOINT_OPENAI_CHAT_COMPLETION,
        "gpt-3.5-turbo-0301" => self::AAT_ENDPOINT_OPENAI_CHAT_COMPLETION,
        "text-davinci-003" => self::AAT_ENDPOINT_OPENAI_TEXT_COMPLETION,
        "text-davinci-002" => self::AAT_ENDPOINT_OPENAI_TEXT_COMPLETION,
        "text-curie-001" => self::AAT_ENDPOINT_OPENAI_TEXT_COMPLETION,
        "text-babbage-001" => self::AAT_ENDPOINT_OPENAI_TEXT_COMPLETION,
        "text-ada-001" => self::AAT_ENDPOINT_OPENAI_TEXT_COMPLETION
    ];

    const AAT_OPENAI_MAX_TOKENS = 70;
    const AAT_OPENAI_TEXT_COMPLETION_TEMPERATURE = 0.6;

    const AAT_OPENAI_CHAT_COMPLETION_ENDPOINT = 'https://api.openai.com/v1/chat/completions';
    const AAT_OPENAI_TEXT_COMPLETION_ENDPOINT = 'https://api.openai.com/v1/completions';

    const AAT_LOG_ASH = 'aat_log_ash';
}