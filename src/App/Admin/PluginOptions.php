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

        add_action('admin_enqueue_scripts', [self::$instance, 'enqueueAdminScripts']);
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
     * Create option page and his fields
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

                echo '<div>';
                echo '<h3>' . __('Typology','auto-alt-text') . '</h3>';
                $typology = get_option(Constants::AAT_OPTION_FIELD_TYPOLOGY);
                ?>
                <select name="<?php echo Constants::AAT_OPTION_FIELD_TYPOLOGY; ?>" id="<?php echo Constants::AAT_OPTION_FIELD_TYPOLOGY; ?>">
                    <option value="<?php echo Constants::AAT_OPTION_TYPOLOGY_CHOICE_AZURE; ?>"<?php echo self::selected($typology, Constants::AAT_OPTION_TYPOLOGY_CHOICE_AZURE); ?>><?php _e('Azure','auto-alt-text'); ?></option>
                    <option value="<?php echo Constants::AAT_OPTION_TYPOLOGY_CHOICE_OPENAI; ?>"<?php echo self::selected($typology, Constants::AAT_OPTION_TYPOLOGY_CHOICE_OPENAI); ?>><?php _e('Open AI','auto-alt-text'); ?></option>
                    <option value="<?php echo Constants::AAT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE; ?>"<?php echo self::selected($typology, Constants::AAT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE); ?>><?php _e('Title of the article','auto-alt-text'); ?></option>
                    <option value="<?php echo Constants::AAT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE; ?>"<?php echo self::selected($typology, Constants::AAT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE); ?>><?php _e('Title of the attachment','auto-alt-text'); ?></option>
                </select>
                <?php
                echo '</div>';


                echo '<div class="plugin-option type-openai">';
                echo '<h3>' . __('OpenAI API Key','auto-alt-text') . '</h3>';
                $apiKey = get_option(Constants::AAT_OPTION_FIELD_API_KEY_OPENAI);
                echo '<input type="password" name="' . Constants::AAT_OPTION_FIELD_API_KEY_OPENAI . '" value="' . (new Encryption())->decrypt($apiKey) . '" />';
                echo '</div>';

                echo '<div class="plugin-option type-openai">';
                echo '<h3>' . __('Prompt','auto-alt-text') . '</h3>';
                $defaultPrompt = sprintf(__("Act like an SEO expert and write an English alt text for this image %s, using a maximum of 15 words. Just return the text without any additional comments.", "auto-alt-text"), Constants::AAT_IMAGE_URL_TAG);
                $prompt = get_option(Constants::AAT_OPTION_FIELD_PROMPT_OPENAI) ?: $defaultPrompt;
                echo '<textarea name="' . Constants::AAT_OPTION_FIELD_PROMPT_OPENAI . '" rows="5" cols="50">' . $prompt . '</textarea>';
                echo '</div>';

                echo '<div class="plugin-option type-openai">';
                echo '<h3>' . __('OpenAi Model','auto-alt-text') . '</h3>';
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
                echo '</div>';

                echo '<div class="plugin-option type-azure">';
                echo '<h3>' . __('Azure Computer Vision API Key','auto-alt-text') . '</h3>';
                $apiKey = get_option(Constants::AAT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION);
                echo '<input type="password" name="' . Constants::AAT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION . '" value="' . (new Encryption())->decrypt($apiKey) . '" />';
                echo '</div>';

                echo '<div class="plugin-option type-azure">';
                echo '<h3>' . __('Azure Computer Vision Endpoint','auto-alt-text') . '</h3>';
                $endpoint = get_option(Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_COMPUTER_VISION);
                echo '<input type="text" name="' . Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_COMPUTER_VISION . '" value="' . $endpoint . '" />';
                echo '</div>';

                echo '<div class="plugin-option type-azure">';
                echo '<h3>' . __('Azure Translate Instance Language','auto-alt-text') . '</h3>';
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

                echo '<div class="plugin-option type-azure not-default-language">';
                echo '<h3>' . __('Azure Translate Instance API Key','auto-alt-text') . '</h3>';
                $apiKey = get_option(Constants::AAT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE);
                echo '<input type="password" name="' . Constants::AAT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE . '" value="' . (new Encryption())->decrypt($apiKey) . '" />';
                echo '</div>';

                echo '<div class="plugin-option type-azure not-default-language">';
                echo '<h3>' . __('Azure Translate Instance Endpoint','auto-alt-text') . '</h3>';
                $endpoint = get_option(Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_TRANSLATE_INSTANCE);
                echo '<input type="text" name="' . Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_TRANSLATE_INSTANCE . '" value="' . $endpoint . '" />';
                echo '</div>';

                echo '<div class="plugin-option type-azure not-default-language">';
                echo '<h3>' . __('Azure Translate Instance Region','auto-alt-text') . '</h3>';
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
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_PROMPT_OPENAI);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_TYPOLOGY);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_MODEL_OPENAI);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_COMPUTER_VISION);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_TRANSLATE_INSTANCE);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_REGION_AZURE_TRANSLATE_INSTANCE);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE);
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

}
