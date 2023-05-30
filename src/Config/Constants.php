<?php

namespace ValerioMonti\AutoAltText\Config;

class Constants
{
    const AAT_OPTION_FIELD_TYPOLOGY = 'aat_typology';
    const AAT_OPTION_FIELD_ENDPOINT = 'aat_endpoint';
    const AAT_OPTION_FIELD_PROMPT = 'aat_prompt_openai';
    const AAT_OPTION_FIELD_MODEL = 'aat_model_openai';
    const AAT_OPTION_FIELD_API_KEY = 'aat_api_key_openai';
    const AAT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE = 'article-title';
    const AAT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE = 'attachment-title';
    const AAT_OPTION_TYPOLOGY_CHOICE_AI = 'openai';
    const AAT_OPTION_ENDPOINT_CHOICE_CHAT_COMPLETION = 'chat-completion';
    const AAT_OPTION_ENDPOINT_CHOICE_TEXT_COMPLETION = 'text-completion';
    const AAT_IMAGE_URL_TAG = '%imageUrl%';
    const AAT_DEFAULT_PROMPT = "Agisci come un esperto SEO e scrivi un alt text in italiano lungo al massimo 15 parole per questa immagine: " . self::AAT_IMAGE_URL_TAG . ". Limitati semplicemente a ritornare il testo senza virgolette.";
    const AAT_DEFAULT_MODEL = "text-davinci-003";
    const AAT_TRANSIENT_OPENAI_API_MODELS = 'aat_transient_ai_models';
}