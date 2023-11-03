<?php

namespace ValerioMonti\AutoAltText\App\Admin;

use OpenAI;
use ValerioMonti\AutoAltText\App\AIProviders\Azure\AzureTranslator;
use ValerioMonti\AutoAltText\App\Logging\FileLogger;
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

        add_action('admin_enqueue_scripts', [self::$instance, 'enqueueAdminScripts'], 1);
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
    public function encryptDataOnUpdate(?string $newValue, ?string $oldValue): ?string
    {
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
        $screen = get_current_screen();
        $isMainOptionsPage = $screen->id === 'toplevel_page_' . Constants::AAT_PLUGIN_OPTIONS_PAGE_SLUG;

        if ($isMainOptionsPage || strpos($screen->id, Constants::AAT_PLUGIN_SLUG . '_') !== false) {
            $entryPoints = AUTO_ALT_TEXT_ABSPATH . '/dist/mix-manifest.json';
            $json = json_decode(file_get_contents($entryPoints), JSON_OBJECT_AS_ARRAY);
            $adminCss = $json['/css/admin.css'];

            wp_enqueue_style(Constants::AAT_PLUGIN_OPTIONS_PAGE_SLUG, AUTO_ALT_TEXT_URL . 'dist' . $adminCss, [], false);

            if ($isMainOptionsPage) {
                $adminJs = $json['/js/admin.js'];
                wp_enqueue_script(Constants::AAT_PLUGIN_OPTIONS_PAGE_SLUG, AUTO_ALT_TEXT_URL . 'dist' . $adminJs, [], false, true);
            }

        }
    }

    /**
     * Aggiunge il link al menu delle opzioni nel pannello di amministrazione di WordPress
     * @return void
     */
    public static function addOptionsPageToTheMenu(): void
    {
        add_menu_page('Auto Alt Text Options', 'Auto Alt Text', 'manage_options', Constants::AAT_PLUGIN_OPTIONS_PAGE_SLUG, [self::$instance, 'optionsMainPage'], null, 99);
        add_submenu_page(Constants::AAT_PLUGIN_OPTIONS_PAGE_SLUG, 'Error Log', 'Error log', 'manage_options', 'auto-alt-text-log', [self::$instance, 'logOptionsPage']);
    }

    public static function logOptionsPage(): void
    {
        $uploadDir = wp_upload_dir();
        $logDir = trailingslashit($uploadDir['basedir']) . Constants::AAT_PLUGIN_SLUG;
        ?>
        <div class="wrap">
            <h1><?php _e('Auto Alt Text Error Log', Constants::AAT_TEXT_DOMAIN) ?></h1>
            <div class="aat-options plugin-description">
                <p><?php _e("On this page, you can view the error log from the last day. The logs from previous days are saved in the folder", Constants::AAT_TEXT_DOMAIN); ?>
                    <strong><?php echo $logDir; ?></strong></p>
                <?php
                $hash = get_option(Constants::AAT_LOG_ASH);
                $logFile = trailingslashit($logDir) . date('Y-m-d') . '-' . $hash . '.log';

                if (!file_exists($logFile)) {
                    $logFile = FileLogger::make()->findLatestLogFile($logDir);
                }

                if ($logFile && file_exists($logFile)) {
                    $logContent = file_get_contents($logFile);
                    echo '<textarea id="error-log" name="error-log" readonly>' . esc_html($logContent) . '</textarea>';
                } else {
                    _e('Log file does not exist', Constants::AAT_TEXT_DOMAIN);
                }

                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Create option page and his fields
     * @return void
     */
    public static function optionsMainPage(): void
    {
        ?>
        <div class="wrap">
            <h1><?php _e('Auto Alt Text Options', Constants::AAT_TEXT_DOMAIN) ?></h1>

            <div class="aat-options plugin-description">
                <p>
                    <?php _e("This plugin allows you to automatically generate Alt Text for the images that are uploaded to the site's media library.", Constants::AAT_TEXT_DOMAIN); ?>
                    <br>
                    <?php _e("The following methods are available to generate the alt text:", Constants::AAT_TEXT_DOMAIN); ?>
                </p>
                <ul>
                    <li>
                        <strong><?php _e("Azure's APIs", Constants::AAT_TEXT_DOMAIN); ?></strong>: <?php _e("the image will be analyzed by the AI services provided by Azure and an alt text will be generated in the language of your choice;", Constants::AAT_TEXT_DOMAIN); ?>
                    </li>
                    <li>
                        <strong><?php _e("Open AI' APIs", Constants::AAT_TEXT_DOMAIN); ?></strong>: <?php _e("based on the prompt you set, an alt text will be created based on the name of the image file you upload to the media library (currently, OpenAI's APIs do not allow to analyze the content of the image);", Constants::AAT_TEXT_DOMAIN); ?>
                    </li>
                    <li>
                        <strong><?php _e("Title of the article (not AI)", Constants::AAT_TEXT_DOMAIN); ?></strong>: <?php _e("if the image is uploaded within an article, the title of the article will be used as alt text;", Constants::AAT_TEXT_DOMAIN); ?>
                    </li>
                    <li>
                        <strong><?php _e("Title of the attachment (not AI)", Constants::AAT_TEXT_DOMAIN); ?></strong>: <?php _e("the title of the attachment will be copied into the alt text;", Constants::AAT_TEXT_DOMAIN); ?>
                    </li>
                </ul>
                <p><?php _e("Once all the necessary data for the chosen generation method has been entered, the alt texts will be created automatically upon uploading each image.", Constants::AAT_TEXT_DOMAIN); ?></p>
                <p style="color:red"><?php _e("Pay attention please: If the alt text for an image is not generated, check the logs on the designated page.", Constants::AAT_TEXT_DOMAIN); ?></p>
            </div>
            <form method="post" action="options.php" class="aat-options">
                <?php
                settings_fields('auto_alt_text_options');

                echo '<div>';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_TYPOLOGY . '">' . __('Generation method', Constants::AAT_TEXT_DOMAIN) . '</label>';
                echo '<p class="description">' . __("Which method do you want to use to generate the alt text for the images?", Constants::AAT_TEXT_DOMAIN) . '</p>';
                $typology = get_option(Constants::AAT_OPTION_FIELD_TYPOLOGY);
                ?>
                <select name="<?php echo Constants::AAT_OPTION_FIELD_TYPOLOGY; ?>"
                        id="<?php echo Constants::AAT_OPTION_FIELD_TYPOLOGY; ?>">
                    <option value="<?php echo Constants::AAT_OPTION_TYPOLOGY_DEACTIVATED; ?>"<?php echo self::selected($typology, Constants::AAT_OPTION_TYPOLOGY_DEACTIVATED); ?>><?php _e("Deactivated", Constants::AAT_TEXT_DOMAIN); ?></option>
                    <option value="<?php echo Constants::AAT_OPTION_TYPOLOGY_CHOICE_AZURE; ?>"<?php echo self::selected($typology, Constants::AAT_OPTION_TYPOLOGY_CHOICE_AZURE); ?>><?php _e("Azure's APIs", Constants::AAT_TEXT_DOMAIN); ?></option>
                    <option value="<?php echo Constants::AAT_OPTION_TYPOLOGY_CHOICE_OPENAI; ?>"<?php echo self::selected($typology, Constants::AAT_OPTION_TYPOLOGY_CHOICE_OPENAI); ?>><?php _e("Open AI' APIs", Constants::AAT_TEXT_DOMAIN); ?></option>
                    <option value="<?php echo Constants::AAT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE; ?>"<?php echo self::selected($typology, Constants::AAT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE); ?>><?php _e("Title of the article (not AI)", Constants::AAT_TEXT_DOMAIN); ?></option>
                    <option value="<?php echo Constants::AAT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE; ?>"<?php echo self::selected($typology, Constants::AAT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE); ?>><?php _e("Title of the attachment (not AI)", Constants::AAT_TEXT_DOMAIN); ?></option>
                </select>
                <?php
                echo '</div>';

                echo '<div class="plugin-option type-openai"><strong>' . __('Warning', Constants::AAT_TEXT_DOMAIN) . '</strong>: ' . __("At the moment, OpenAI's APIs do not offer the possibility to describe an image with computer vision.", Constants::AAT_TEXT_DOMAIN) . ' ' . __("Therefore, for the time being, by filling out the following fields you will be able to generate an alt text based solely on the name of the image file.", Constants::AAT_TEXT_DOMAIN) . '<br>' . __("If you want an accurate description of the image, use Azure's APIs.", Constants::AAT_TEXT_DOMAIN) . '</div>';

                echo '<div class="plugin-option type-openai">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_API_KEY_OPENAI . '">' . __('OpenAI API Key', Constants::AAT_TEXT_DOMAIN) . '</label>';
                echo '<p class="description">' . __("Enter your API Key", Constants::AAT_TEXT_DOMAIN) . '</p>';
                $apiKey = get_option(Constants::AAT_OPTION_FIELD_API_KEY_OPENAI);
                echo '<input type="password" name="' . Constants::AAT_OPTION_FIELD_API_KEY_OPENAI . '" value="' . (new Encryption())->decrypt($apiKey) . '" />';
                echo '</div>';

                echo '<div class="plugin-option type-openai">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_PROMPT_OPENAI . '">' . __('Prompt', Constants::AAT_TEXT_DOMAIN) . '</label>';
                echo '<p class="description">' . __("Enter a specific and detailed prompt according to your needs.", Constants::AAT_TEXT_DOMAIN) . '</p>';
                $defaultPrompt = sprintf(__("Act like an SEO expert and write an English alt text for this image %s, using a maximum of 15 words. Just return the text without any additional comments.", Constants::AAT_TEXT_DOMAIN), Constants::AAT_IMAGE_URL_TAG);
                $prompt = get_option(Constants::AAT_OPTION_FIELD_PROMPT_OPENAI) ?: $defaultPrompt;
                echo '<textarea name="' . Constants::AAT_OPTION_FIELD_PROMPT_OPENAI . '" rows="5" cols="50">' . $prompt . '</textarea>';
                echo '</div>';

                echo '<div class="plugin-option type-openai">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_MODEL_OPENAI . '">' . __('OpenAi Model', Constants::AAT_TEXT_DOMAIN) . '</label>';
                echo '<p class="description">' . __("Choose the OpenAI model you want to use to generate the alt text.", Constants::AAT_TEXT_DOMAIN) . '</p>';
                $modelSaved = get_option(Constants::AAT_OPTION_FIELD_MODEL_OPENAI);
                ?>

                <select name="<?php echo Constants::AAT_OPTION_FIELD_MODEL_OPENAI; ?>"
                        id="<?php echo Constants::AAT_OPTION_FIELD_MODEL_OPENAI; ?>">
                    <?php
                    foreach (Constants::AAT_OPENAI_MODELS as $modelName => $a) :
                        ?>
                        <option value="<?php echo $modelName; ?>" <?php echo self::isModelSelected($modelSaved, $modelName) ? 'selected="selected"' : ''; ?>><?php echo $modelName; ?></option>
                    <?php
                    endforeach;
                    ?>
                </select>

                <?php
                echo '</div>';

                echo '<div class="plugin-option type-azure">' . __("Fill out the following fields to leverage Azure's computer vision services to generate the Alt texts.", Constants::AAT_TEXT_DOMAIN) . '</div>';

                echo '<div class="plugin-option type-azure">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION . '">' . __('Azure Computer Vision API Key', Constants::AAT_TEXT_DOMAIN) . '</label>';
                echo '<p class="description">' . __("Enter the API key for the Computer Vision service of your Azure account.", Constants::AAT_TEXT_DOMAIN) . '</p>';
                $apiKey = get_option(Constants::AAT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION);
                echo '<input type="password" name="' . Constants::AAT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION . '" value="' . (new Encryption())->decrypt($apiKey) . '" />';
                echo '</div>';

                echo '<div class="plugin-option type-azure">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_COMPUTER_VISION . '">' . __('Azure Computer Vision Endpoint', Constants::AAT_TEXT_DOMAIN) . '</label>';
                echo '<p class="description">' . __("Enter the endpoint of the Computer Vision service.", Constants::AAT_TEXT_DOMAIN) . ' (es. https://computer-vision-france-central.cognitiveservices.azure.com/)</p>';
                $endpoint = get_option(Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_COMPUTER_VISION);
                echo '<input type="text" name="' . Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_COMPUTER_VISION . '" value="' . $endpoint . '" />';
                echo '</div>';

                echo '<div class="plugin-option type-azure">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE . '">' . __('Alt Text Language', Constants::AAT_TEXT_DOMAIN) . '</label>';
                echo '<p class="description">' . __("Select the language in which the alt text should be written.", Constants::AAT_TEXT_DOMAIN) . '</p>';
                $currentLanguage = get_option(Constants::AAT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE);
                $supportedLanguages = (AzureTranslator::make())->supportedLanguages();
                ?>
                <select name="<?php echo Constants::AAT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE; ?>"
                        id="<?php echo Constants::AAT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE; ?>">
                    <?php
                    foreach ($supportedLanguages as $key => $language):
                        ?>
                        <option value="<?php echo $key ?>" <?php echo self::selected($currentLanguage, $key); ?>><?php echo $language['name'] ?></option>
                    <?php
                    endforeach;
                    ?>
                </select>
                <?php
                echo '</div>';

                echo '<div class="plugin-option type-azure not-default-language"><strong>' . __("Warning", Constants::AAT_TEXT_DOMAIN) . '</strong>: ' . __("The default language is English. You have selected a different language, therefore it is necessary to enter the required information in order to translate the alt text using the Azure Translation Instance service.", Constants::AAT_TEXT_DOMAIN) . '</div>';

                echo '<div class="plugin-option type-azure not-default-language">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE . '">' . __('Azure Translate Instance API Key', Constants::AAT_TEXT_DOMAIN) . '</label>';
                echo '<p class="description">' . __("Enter your API key for the Azure Translate Instance service.", Constants::AAT_TEXT_DOMAIN) . '</p>';
                $apiKey = get_option(Constants::AAT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE);
                echo '<input type="password" name="' . Constants::AAT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE . '" value="' . (new Encryption())->decrypt($apiKey) . '" />';
                echo '</div>';

                echo '<div class="plugin-option type-azure not-default-language">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_TRANSLATE_INSTANCE . '">' . __('Azure Translate Instance Endpoint', Constants::AAT_TEXT_DOMAIN) . '</label>';
                echo '<p class="description">' . __("Enter the endpoint of the Translate Instance service", Constants::AAT_TEXT_DOMAIN) . ' (es. https://api.cognitive.microsofttranslator.com/)</p>';
                $endpoint = get_option(Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_TRANSLATE_INSTANCE);
                echo '<input type="text" name="' . Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_TRANSLATE_INSTANCE . '" value="' . $endpoint . '" />';
                echo '</div>';

                echo '<div class="plugin-option type-azure not-default-language">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_REGION_AZURE_TRANSLATE_INSTANCE . '">' . __('Azure Translate Instance Region', Constants::AAT_TEXT_DOMAIN) . '</label>';
                echo '<p class="description">' . __("Enter the region of the Azure Translate Instance service.", Constants::AAT_TEXT_DOMAIN) . ' (es. westeurope)</p>';
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
    public static function selected(string $selectedValue, string $inputValue): string
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

    public static function sanitizeUrl($input): string
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
