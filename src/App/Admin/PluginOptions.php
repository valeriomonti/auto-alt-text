<?php

namespace ValerioMonti\AutoAltText\App\Admin;

use OpenAI;
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

        add_action('admin_enqueue_scripts', [self::$instance, 'enqueueAdminScripts']);
        add_action('admin_menu', [self::$instance, 'addOptionsPageToTheMenu']);
        add_action('admin_init', [self::$instance, 'setupPluginOptions']);
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
            $adminCss = $json['/css/admin-style.css'];

            wp_enqueue_script(Constants::AAT_PLUGIN_OPTIONS_PAGE_SLUG, AUTO_ALT_TEXT_URL . 'dist' . $adminJs, [], false, true);
            wp_enqueue_style(Constants::AAT_PLUGIN_OPTIONS_PAGE_SLUG, AUTO_ALT_TEXT_URL . 'dist' . $adminCss, [], false, true);

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
     * Crea la pagina delle opzioni e i campi di input
     * @return void
     */
    public static function optionsPageContent()
    {
        ?>
        <div class="wrap">
            <h1>Auto Alt Text Options</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('auto_alt_text_options');
                do_settings_sections('auto_alt_text_options');
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
     * Registra i campi di input e le impostazioni delle opzioni
     * @return void
     */
    public static function setupPluginOptions(): void
    {
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_API_KEY_OPENAI);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_PROMPT_OPENAI);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_TYPOLOGY);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_MODEL_OPENAI);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_API_KEY_AZURE);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE);


        add_settings_section('auto_alt_text_section', __('Plugin options','auto-alt-text'), [self::$instance, 'autoAltTextOptionsSection'], 'auto_alt_text_options');

        // Open AI options
        add_settings_field(Constants::AAT_OPTION_FIELD_TYPOLOGY, __('Typology','auto-alt-text'), [self::$instance, 'autoAltTextTypologyCallback'], 'auto_alt_text_options', 'auto_alt_text_section');
        add_settings_field(Constants::AAT_OPTION_FIELD_MODEL_OPENAI, __('Model','auto-alt-text'), [self::$instance, 'autoAltTextAiModelCallback'], 'auto_alt_text_options', 'auto_alt_text_section', ['class' => 'plugin-option type-openai']);
        add_settings_field(Constants::AAT_OPTION_FIELD_API_KEY_OPENAI, __('OpenAI API Key','auto-alt-text'), [self::$instance, 'autoAltTextOpenAIApiKeyCallback'], 'auto_alt_text_options', 'auto_alt_text_section', ['class' => 'plugin-option type-openai']);
        add_settings_field(Constants::AAT_OPTION_FIELD_PROMPT_OPENAI, __('Prompt','auto-alt-text'), [self::$instance, 'autoAltTextPromptCallback'], 'auto_alt_text_options', 'auto_alt_text_section', ['class' => 'plugin-option type-openai']);

        //Azure Options
        add_settings_field(Constants::AAT_OPTION_FIELD_API_KEY_AZURE, __('Azure API Key','auto-alt-text'), [self::$instance, 'autoAltTextAzureApiKeyCallback'], 'auto_alt_text_options', 'auto_alt_text_section', ['class' => 'plugin-option type-azure']);
        add_settings_field(Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE, __('Azure Endpoint','auto-alt-text'), [self::$instance, 'autoAltTextAzureEndpointCallback'], 'auto_alt_text_options', 'auto_alt_text_section', ['class' => 'plugin-option type-azure']);

    }

    /**
     * Callback per la sezione delle opzioni
     * @return void
     */
    public static function autoAltTextOptionsSection(): void
    {
        _e('Customize options','auto-alt-text');
    }

    /**
     * Callback per il campo Api Key OPen AI
     * @return void
     */
    public static function autoAltTextOpenAIApiKeyCallback(): void
    {
        $apiKey = get_option(Constants::AAT_OPTION_FIELD_API_KEY_OPENAI);
        echo '<input type="password" name="' . Constants::AAT_OPTION_FIELD_API_KEY_OPENAI . '" value="' . $apiKey . '" />';
    }

    /**
     * Callback per il campo Api Key Azure
     * @return void
     */
    public static function autoAltTextAzureApiKeyCallback(): void
    {
        $apiKey = get_option(Constants::AAT_OPTION_FIELD_API_KEY_AZURE);
        echo '<input type="password" name="' . Constants::AAT_OPTION_FIELD_API_KEY_AZURE . '" value="' . $apiKey . '" />';
    }

    /**
     * @return void
     */
    public static function autoAltTextAzureEndpointCallback(): void
    {
        $endpoint = get_option(Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE);
        echo '<input type="text" name="' . Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE . '" value="' . $endpoint . '" />';
    }

    /**
     * Callback per il campo Prompt
     * @return void
     */
    public static function autoAltTextPromptCallback(): void
    {
        $defaultPrompt = sprintf(__("Act like an SEO expert and write an English alt text for this image %s, using a maximum of 15 words. Just return the text without any additional comments.", "auto-alt-text"), Constants::AAT_IMAGE_URL_TAG);
        $prompt = get_option(Constants::AAT_OPTION_FIELD_PROMPT_OPENAI) ?: $defaultPrompt;

        echo '<textarea name="' . Constants::AAT_OPTION_FIELD_PROMPT_OPENAI . '" rows="5" cols="50">' . $prompt . '</textarea>';
    }

    /**
     * Callback per il campo Tipologia
     * @return void
     */
    public static function autoAltTextTypologyCallback(): void
    {
        $typology = get_option(Constants::AAT_OPTION_FIELD_TYPOLOGY);
        ?>
        <select name="<?php echo Constants::AAT_OPTION_FIELD_TYPOLOGY; ?>" id="<?php echo Constants::AAT_OPTION_FIELD_TYPOLOGY; ?>">
            <option value="<?php echo Constants::AAT_OPTION_TYPOLOGY_CHOICE_AZURE; ?>"<?php echo self::selected($typology, Constants::AAT_OPTION_TYPOLOGY_CHOICE_AZURE); ?>><?php _e('Azure','auto-alt-text'); ?></option>
            <option value="<?php echo Constants::AAT_OPTION_TYPOLOGY_CHOICE_OPENAI; ?>"<?php echo self::selected($typology, Constants::AAT_OPTION_TYPOLOGY_CHOICE_OPENAI); ?>><?php _e('Open AI','auto-alt-text'); ?></option>
            <option value="<?php echo Constants::AAT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE; ?>"<?php echo self::selected($typology, Constants::AAT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE); ?>><?php _e('Title of the article','auto-alt-text'); ?></option>
            <option value="<?php echo Constants::AAT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE; ?>"<?php echo self::selected($typology, Constants::AAT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE); ?>><?php _e('Title of the attachment','auto-alt-text'); ?></option>
        </select>

        <?php
    }

    public static function isModelSelected($modelSaved, $currentModel): bool
    {
        if (empty($modelSaved)) {
            return Constants::AAT_OPENAI_DEFAULT_MODEL == $currentModel;
        }

        return $modelSaved == $currentModel;
    }

    public static function autoAltTextAiModelCallback(): void
    {
        $modelSaved = get_option(Constants::AAT_OPTION_FIELD_MODEL_OPENAI);
        ?>
            <label>
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
            </label>
        <?php
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
        return get_option(Constants::AAT_OPTION_FIELD_API_KEY_OPENAI);
    }

    /**
     * @return string
     */
    public static function apiKeyAzure(): string
    {
        return get_option(Constants::AAT_OPTION_FIELD_API_KEY_AZURE);
    }

    public static function endpointAzure(): string
    {
        return get_option(Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE);
    }

    /**
     * @return string
     */
    public static function model(): string
    {
        return get_option(Constants::AAT_OPTION_FIELD_MODEL_OPENAI);
    }

}
