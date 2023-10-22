<?php

namespace ValerioMonti\AutoAltText\App\Admin;

use OpenAI;
use ValerioMonti\AutoAltText\App\AIProviders\Azure\AzureTranslator;
use ValerioMonti\AutoAltText\App\Utilities\Encryption;
use ValerioMonti\AutoAltText\Config\Constants;

class PluginOptions
{
    private static ?self $instance = null;

    private function __construct()
    {

    }

    /**
     * @return void
     */
    public static function register(): void
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        add_action('admin_enqueue_scripts', [self::$instance, 'enqueueAdminScripts'],1);
        add_action('admin_menu', [self::$instance, 'addOptionsPageToTheMenu']);
        add_action('admin_init', [self::$instance, 'setupPluginOptions']);

        add_action('pre_update_option_' . Constants::AAT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION, [self::$instance, 'encryptDataOnUpdate'], 10, 3);
        add_action('pre_update_option_' . Constants::AAT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE, [self::$instance, 'encryptDataOnUpdate'], 10, 3);
        add_action('pre_update_option_' . Constants::AAT_OPTION_FIELD_API_KEY_OPENAI, [self::$instance, 'encryptDataOnUpdate'], 10, 3);
    }

    /**
     * @param ?string $newValue
     * @param ?string $oldValue
     * @return ?string
     */
    public function encryptDataOnUpdate(?string $newValue, ?string $oldValue): ?string {
        if (!empty($newValue)) {
            $newValue = (new Encryption())->encrypt($newValue);
        }
        return $newValue;
    }

    /**
     * @return void
     */
    public static function enqueueAdminScripts(): void
    {
        if ( array_key_exists('page',  $_GET) && Constants::AAT_PLUGIN_OPTIONS_PAGE_SLUG == $_GET["page"]) {
            $entryPoints = AUTO_ALT_TEXT_ABSPATH .'/dist/mix-manifest.json';
            $json = json_decode(file_get_contents($entryPoints), JSON_OBJECT_AS_ARRAY);
            $adminJs = $json['/js/admin.js'];
            $adminCss = $json['/css/admin.css'];

            wp_enqueue_script(Constants::AAT_PLUGIN_OPTIONS_PAGE_SLUG, AUTO_ALT_TEXT_URL . 'dist' . $adminJs, [], false, true);
            wp_enqueue_style(Constants::AAT_PLUGIN_OPTIONS_PAGE_SLUG, AUTO_ALT_TEXT_URL . 'dist' . $adminCss, [], false);

        }
    }

    /**
     * Aggiunge il link al menu delle opzioni nel pannello di amministrazione di WordPress
     * @return void
     */
    public static function addOptionsPageToTheMenu(): void
    {
        add_options_page('Auto Alt Text Options', 'Auto Alt Text Options', 'manage_options', 'auto-alt-text-options', [self::$instance, 'optionsPageContent']);
    }

    /**
     * Create option page and his fields
     * @return void
     */
    public static function optionsPageContent()
    {
        ?>
        <div class="wrap">
            <h1>Auto Alt Text Options</h1>

            <div class="aat-options plugin-description">
                <p>
                    Questo plugin ti permette di generare in modo automatico un Alt Text per le immagini che vengono caricate nella media library del sito.<br>
                    Sono disponibili i seguenti metodi per generare l'alt text:
                </p>
                <ul>
                    <li><strong>Azure's APIs</strong>: l'immagine verrà analizzata dai servizi di AI forniti da Azure e verrà generato un alt text nella lingua che preferisci;</li>
                    <li><strong>OperAi's APIs</strong>: in base al prompt che imposterai verrà creato un alt text basato sul nome del file immagine che caricherai nella media library (al momento le API di Open ai non consentono di analizzare il contenuto dell'immagine);</li>
                    <li><strong>Title of the article</strong>: se l'immagine è caricata in un articolo, il titolo dell'articolo verrà utilizzato come alt text</li>
                    <li><strong>Title of the attachment</strong>: verrà copiato nell'alt text il titolo dell'attachment</li>
                </ul>
                <p>Una volta inseriti tutti i dati necessari per il metodo di generazione scelto, gli alt text verranno creati automaticamente al caricamento di ogni immagine.</p>
            </div>
            <form method="post" action="options.php" class="aat-options">
                <?php
                settings_fields('auto_alt_text_options');

                echo '<div>';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_TYPOLOGY . '">' . __('Generation method','auto-alt-text') . '</label>';
                echo '<p class="description">Quale metodo vuoi utilizzare per generare l\'alt text delle immagini?</p>';
                $typology = get_option(Constants::AAT_OPTION_FIELD_TYPOLOGY);
                ?>
                <select name="<?php echo Constants::AAT_OPTION_FIELD_TYPOLOGY; ?>" id="<?php echo Constants::AAT_OPTION_FIELD_TYPOLOGY; ?>">
                    <option value="<?php echo Constants::AAT_OPTION_TYPOLOGY_DEACTIVATED; ?>"<?php echo self::selected($typology, Constants::AAT_OPTION_TYPOLOGY_DEACTIVATED); ?>><?php _e('Deactivated','auto-alt-text'); ?></option>
                    <option value="<?php echo Constants::AAT_OPTION_TYPOLOGY_CHOICE_AZURE; ?>"<?php echo self::selected($typology, Constants::AAT_OPTION_TYPOLOGY_CHOICE_AZURE); ?>><?php _e('Azure\'s APIs','auto-alt-text'); ?></option>
                    <option value="<?php echo Constants::AAT_OPTION_TYPOLOGY_CHOICE_OPENAI; ?>"<?php echo self::selected($typology, Constants::AAT_OPTION_TYPOLOGY_CHOICE_OPENAI); ?>><?php _e('Open AI\' APIs','auto-alt-text'); ?></option>
                    <option value="<?php echo Constants::AAT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE; ?>"<?php echo self::selected($typology, Constants::AAT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE); ?>><?php _e('Title of the article (not AI)','auto-alt-text'); ?></option>
                    <option value="<?php echo Constants::AAT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE; ?>"<?php echo self::selected($typology, Constants::AAT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE); ?>><?php _e('Title of the attachment (not AI)','auto-alt-text'); ?></option>
                </select>
                <?php
                echo '</div>';

                echo '<div class="plugin-option type-openai"><strong>Attenzione</strong>: al momento le api di OpenAi non offrono la possibilità di descrivere un\'immagine con la computer vision.
                        Per il momento quindi, compilando i seguenti campi potrai generare un alt text basato solamente sul nome del file immagine.<br>Se desideri una descrizione accurata utilizza le api di Azure</div>';

                echo '<div class="plugin-option type-openai">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_API_KEY_OPENAI . '">' . __('OpenAI API Key','auto-alt-text') . '</label>';
                echo '<p class="description">Inserisci la tua API Key</p>';
                $apiKey = get_option(Constants::AAT_OPTION_FIELD_API_KEY_OPENAI);
                echo '<input type="password" name="' . Constants::AAT_OPTION_FIELD_API_KEY_OPENAI . '" value="' . (new Encryption())->decrypt($apiKey) . '" />';
                echo '</div>';

                echo '<div class="plugin-option type-openai">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_PROMPT_OPENAI . '">' . __('Prompt','auto-alt-text') . '</label>';
                echo '<p class="description">Inserisci un prompt specifico e dettagliato in base alle tue esigenze</p>';
                $defaultPrompt = sprintf(__("Act like an SEO expert and write an English alt text for this image %s, using a maximum of 15 words. Just return the text without any additional comments.", "auto-alt-text"), Constants::AAT_IMAGE_URL_TAG);
                $prompt = get_option(Constants::AAT_OPTION_FIELD_PROMPT_OPENAI) ?: $defaultPrompt;
                echo '<textarea name="' . Constants::AAT_OPTION_FIELD_PROMPT_OPENAI . '" rows="5" cols="50">' . $prompt . '</textarea>';
                echo '</div>';

                echo '<div class="plugin-option type-openai">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_MODEL_OPENAI . '">' . __('OpenAi Model','auto-alt-text') . '</label>';
                echo '<p class="description">Scegli il modello di Open Ai che vuoi utilizzare per generare l\'alt tag</p>';
                $modelSaved = get_option(Constants::AAT_OPTION_FIELD_MODEL_OPENAI);
                ?>

                <select name="<?php echo Constants::AAT_OPTION_FIELD_MODEL_OPENAI; ?>"
                        id="<?php echo Constants::AAT_OPTION_FIELD_MODEL_OPENAI; ?>">
                    <?php
                    foreach(Constants::AAT_OPENAI_MODELS as $modelName => $a) :
                        ?>
                        <option value="<?php echo $modelName; ?>" <?php echo self::isModelSelected($modelSaved, $modelName) ? 'selected="selected"' : ''; ?>><?php echo $modelName; ?></option>
                    <?php
                    endforeach;
                    ?>
                </select>

                <?php
                echo '</div>';

                echo '<div class="plugin-option type-azure">Compila i seguenti campi per sfruttare i servizi di computer vision di Azure per generare gli Alt text.</div>';

                echo '<div class="plugin-option type-azure">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION . '">' . __('Azure Computer Vision API Key','auto-alt-text') . '</label>';
                echo '<p class="description">Inserisci la chiave API del servizio Computer Vision del tuo account Azure</p>';
                $apiKey = get_option(Constants::AAT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION);
                echo '<input type="password" name="' . Constants::AAT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION . '" value="' . (new Encryption())->decrypt($apiKey) . '" />';
                echo '</div>';

                echo '<div class="plugin-option type-azure">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_COMPUTER_VISION . '">' . __('Azure Computer Vision Endpoint','auto-alt-text') . '</label>';
                echo '<p class="description">Inserisci l\'endpoint del servizio di Computer Vision (es. https://computer-vision-france-central.cognitiveservices.azure.com/)</p>';
                $endpoint = get_option(Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_COMPUTER_VISION);
                echo '<input type="text" name="' . Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_COMPUTER_VISION . '" value="' . $endpoint . '" />';
                echo '</div>';

                echo '<div class="plugin-option type-azure">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE . '">' . __('Alt Text Language','auto-alt-text') . '</label>';
                echo '<p class="description">Seleziona la lingua in cui deve essere scritto l\'alt text</p>';
                $currentLanguage = get_option(Constants::AAT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE);
                $supportedLanguages = (AzureTranslator::make())->supportedLanguages();
                ?>
                <select name="<?php echo Constants::AAT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE; ?>" id="<?php echo Constants::AAT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE; ?>">
                    <?php
                    foreach($supportedLanguages as $key => $language):
                        ?>
                        <option value="<?php echo $key ?>" <?php echo self::selected($currentLanguage, $key); ?>><?php echo $language['name'] ?></option>
                    <?php
                    endforeach;
                    ?>
                </select>
                <?php
                echo '</div>';

                echo '<div class="plugin-option type-azure not-default-language"><strong>Attenzione</strong>: la lingua di default è l\'inglese. Hai selezionato una lingua diversa pertanto è necesario inserire le informazioni necessarie per poter tradurre l\'alt text sfruttando il servizio Translation Instance di Azure</div>';

                echo '<div class="plugin-option type-azure not-default-language">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE . '">' . __('Azure Translate Instance API Key','auto-alt-text') . '</label>';
                echo '<p class="description">Inserisci la tua chiave API per il servizio Translate Instance di Azure</p>';
                $apiKey = get_option(Constants::AAT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE);
                echo '<input type="password" name="' . Constants::AAT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE . '" value="' . (new Encryption())->decrypt($apiKey) . '" />';
                echo '</div>';

                echo '<div class="plugin-option type-azure not-default-language">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_TRANSLATE_INSTANCE . '">' . __('Azure Translate Instance Endpoint','auto-alt-text') . '</label>';
                echo '<p class="description">Inserisci l\'endpoint del servizio Translate Instance (es. https://api.cognitive.microsofttranslator.com/)</p>';
                $endpoint = get_option(Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_TRANSLATE_INSTANCE);
                echo '<input type="text" name="' . Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_TRANSLATE_INSTANCE . '" value="' . $endpoint . '" />';
                echo '</div>';

                echo '<div class="plugin-option type-azure not-default-language">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_REGION_AZURE_TRANSLATE_INSTANCE . '">' . __('Azure Translate Instance Region','auto-alt-text') . '</label>';
                echo '<p class="description">Inserire la regione del servizio Translate Instance di Azure (es. westeurope)</p>';
                $region = get_option(Constants::AAT_OPTION_FIELD_REGION_AZURE_TRANSLATE_INSTANCE);
                echo '<input type="text" name="' . Constants::AAT_OPTION_FIELD_REGION_AZURE_TRANSLATE_INSTANCE . '" value="' . $region . '" />';
                echo '</div>';

                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * @param string $selectedValue
     * @param string $inputValue
     * @return string
     */
    public static function selected(string $selectedValue, string $inputValue) : string
    {
        return $selectedValue == $inputValue ? ' selected' : '';
    }


    /**
     * Register input fields and option settings
     * @return void
     */
    public static function setupPluginOptions(): void
    {
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_API_KEY_OPENAI);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_PROMPT_OPENAI, [self::class, 'sanitizeTextArea']);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_TYPOLOGY, [self::class, 'sanitizeText']);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_MODEL_OPENAI, [self::class, 'sanitizeText']);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_COMPUTER_VISION, [self::class, 'sanitizeUrl']);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_TRANSLATE_INSTANCE, [self::class, 'sanitizeUrl']);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_REGION_AZURE_TRANSLATE_INSTANCE, [self::class, 'sanitizeText']);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE, [self::class, 'sanitizeText']);
    }

    public static function isModelSelected($modelSaved, $currentModel): bool
    {
        if (empty($modelSaved)) {
            return Constants::AAT_OPENAI_DEFAULT_MODEL == $currentModel;
        }

        return $modelSaved == $currentModel;
    }

    /**
     * @return string
     */
    public static function typology(): string
    {
        return get_option(Constants::AAT_OPTION_FIELD_TYPOLOGY);
    }

    /**
     * @return string
     */
    public static function prompt(): string
    {
        return get_option(Constants::AAT_OPTION_FIELD_PROMPT_OPENAI);
    }

    /**
     * @return string
     */
    public static function apiKeyOpenAI(): string
    {
        $apiKey = get_option(Constants::AAT_OPTION_FIELD_API_KEY_OPENAI);
        return (new Encryption())->decrypt($apiKey);
    }

    /**
     * @return string
     */
    public static function apiKeyAzureComputerVision(): string
    {
        $apiKey = get_option(Constants::AAT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION);
        return (new Encryption())->decrypt($apiKey);
    }

    /**
     * @return string
     */
    public static function endpointAzureComputerVision(): string
    {
        return get_option(Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_COMPUTER_VISION);
    }

    /**
     * @return string
     */
    public static function apiKeyAzureTranslateInstance(): string
    {
        $apiKey = get_option(Constants::AAT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE);
        return (new Encryption())->decrypt($apiKey);
    }

    /**
     * @return string
     */
    public static function endpointAzureTranslateInstance(): string
    {
        return get_option(Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_TRANSLATE_INSTANCE);
    }

    /**
     * @return string
     */
    public static function regionAzureTranslateInstance(): string
    {
        return get_option(Constants::AAT_OPTION_FIELD_REGION_AZURE_TRANSLATE_INSTANCE);
    }

    /**
     * @return string
     */
    public static function languageAzureTranslateInstance(): string
    {
        return get_option(Constants::AAT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE);
    }

    /**
     * @return string
     */
    public static function model(): string
    {
        return get_option(Constants::AAT_OPTION_FIELD_MODEL_OPENAI);
    }

    public static function sanitizeUrl($input):string
    {
        return sanitize_url($input);
    }

    public static function sanitizeText($input): string
    {
        return sanitize_text_field($input);
    }

    public static function sanitizeTextArea($input): string
    {
        return sanitize_textarea_field($input);
    }

}
