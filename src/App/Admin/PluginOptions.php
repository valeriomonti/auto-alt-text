<?php

namespace ValerioMonti\AutoAltText\App\Admin;

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
    public static function addOptionsPageToTheMenu():void {
        add_options_page('Auto Alt Text Options', 'Auto Alt Text Options', 'manage_options', 'auto-alt-text-options', [self::$instance, 'optionsPageContent']);
    }

    // Crea la pagina delle opzioni e i campi di input
    public static function optionsPageContent() {
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
    public static function setupPluginOptions() {
        register_setting('auto_alt_text_options', 'api_key');
        register_setting('auto_alt_text_options', 'prompt');
        register_setting('auto_alt_text_options', 'typology');

        add_settings_section('auto_alt_text_section', 'Impostazioni del Plugin', [self::$instance, 'autoAltTextOptionsSection'], 'auto_alt_text_options');

        add_settings_field('api_key', 'API Key', [self::$instance, 'autoAltTextapiKeyCallback'], 'auto_alt_text_options', 'auto_alt_text_section');
        add_settings_field('prompt', 'Prompt', [self::$instance, 'autoAltTextPromptCallback'], 'auto_alt_text_options', 'auto_alt_text_section');
        add_settings_field('typology', 'Tipologia', [self::$instance, 'autoAltTextTypologyCallback'], 'auto_alt_text_options', 'auto_alt_text_section');
    }


    // Callback per la sezione delle opzioni
    public static function autoAltTextOptionsSection(): void
    {
        echo 'Personalizza le opzioni del plugin:';
    }

    // Callback per il campo Api Key
    public static function autoAltTextapiKeyCallback() {
        $api_key = get_option('api_key');
        echo "<input type='password' name='api_key' value='$api_key' />";
    }

    // Callback per il campo Prompt
    public static function autoAltTextPromptCallback() {
        $prompt = get_option('prompt');
        echo "<textarea name='prompt' rows='5' cols='50'>$prompt</textarea>";
    }

    // Callback per il campo Tipologia
    public static function autoAltTextTypologyCallback() {
        $typology = get_option('typology');
        ?>
        <label>
            <input type="radio" name="typology" value="gpt4" <?php checked($typology, 'gpt4'); ?> />
            GPT 4
        </label>
        <br>
        <label>
            <input type="radio" name="typology" value="article-title" <?php checked($typology, 'article-title'); ?> />
            Titolo dell'articolo
        </label>
        <br>
        <label>
            <input type="radio" name="typology" value="file-name" <?php checked($typology, 'file-name'); ?> />
            Nome del file
        </label>
        <?php
    }
}