<?php

namespace ValerioMonti\AutoAltText\App\Admin;

use ValerioMonti\AutoAltText\Config\Constants;

class PluginOptions
{
    private static ?self $instance = null;

    private function __construct()
    {

    }

    public static function register(): void
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        add_action('admin_menu', [self::$instance, 'addOptionsPageToTheMenu']);
        add_action('admin_init', [self::$instance, 'setupPluginOptions']);
    }

    // Aggiunge il link al menu delle opzioni nel pannello di amministrazione di WordPress
    public static function addOptionsPageToTheMenu(): void
    {
        add_options_page('Auto Alt Text Options', 'Auto Alt Text Options', 'manage_options', 'auto-alt-text-options', [self::$instance, 'optionsPageContent']);
    }

    // Crea la pagina delle opzioni e i campi di input
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


// Registra i campi di input e le impostazioni delle opzioni
    public static function setupPluginOptions(): void
    {
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_API_KEY);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_PROMPT);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_TYPOLOGY);

        add_settings_section('auto_alt_text_section', 'Impostazioni del Plugin', [self::$instance, 'autoAltTextOptionsSection'], 'auto_alt_text_options');

        add_settings_field(Constants::AAT_OPTION_FIELD_API_KEY, 'API Key', [self::$instance, 'autoAltTextapiKeyCallback'], 'auto_alt_text_options', 'auto_alt_text_section');
        add_settings_field(Constants::AAT_OPTION_FIELD_PROMPT, 'Prompt', [self::$instance, 'autoAltTextPromptCallback'], 'auto_alt_text_options', 'auto_alt_text_section');
        add_settings_field(Constants::AAT_OPTION_FIELD_TYPOLOGY, 'Typology', [self::$instance, 'autoAltTextTypologyCallback'], 'auto_alt_text_options', 'auto_alt_text_section');
    }


    // Callback per la sezione delle opzioni
    public static function autoAltTextOptionsSection(): void
    {
        echo 'Personalizza le opzioni del plugin:';
    }

    // Callback per il campo Api Key
    public static function autoAltTextapiKeyCallback()
    {
        $api_key = get_option(Constants::AAT_OPTION_FIELD_API_KEY);
        echo '<input type="password" name="' . Constants::AAT_OPTION_FIELD_API_KEY . '" value="' . $api_key . '" />';
    }

    // Callback per il campo Prompt
    public static function autoAltTextPromptCallback()
    {
        $prompt = get_option(Constants::AAT_OPTION_FIELD_PROMPT);
        echo '<textarea name="' . Constants::AAT_OPTION_FIELD_PROMPT . '" rows="5" cols="50">' . $prompt . '</textarea>';
    }

    // Callback per il campo Tipologia
    public static function autoAltTextTypologyCallback()
    {
        $typology = get_option(Constants::AAT_OPTION_FIELD_TYPOLOGY);
        ?>
        <label>
            <input type="radio" name="<?php echo Constants::AAT_OPTION_FIELD_TYPOLOGY; ?>"
                   value="<?php echo Constants::AAT_OPTION_TYPOLOGY_CHOICE_AI; ?>" <?php checked($typology, Constants::AAT_OPTION_TYPOLOGY_CHOICE_AI); ?> />
            Open AI
        </label>
        <br>
        <label>
            <input type="radio" name="<?php echo Constants::AAT_OPTION_FIELD_TYPOLOGY; ?>"
                   value="<?php echo Constants::AAT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE; ?>" <?php checked($typology, Constants::AAT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE); ?> />
            Title of the article
        </label>
        <br>
        <label>
            <input type="radio" name="<?php echo Constants::AAT_OPTION_FIELD_TYPOLOGY; ?>"
                   value="<?php echo Constants::AAT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE; ?>" <?php checked($typology, Constants::AAT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE); ?> />
            Title of the attachment
        </label>
        <?php
    }

    public static function prompt(): string
    {
        return get_option(Constants::AAT_OPTION_FIELD_PROMPT);
    }

    public static function typology(): string
    {
        return get_option(Constants::AAT_OPTION_FIELD_TYPOLOGY);
    }

    public static function apiKey(): string
    {
        return get_option(Constants::AAT_OPTION_FIELD_API_KEY);
    }

}
