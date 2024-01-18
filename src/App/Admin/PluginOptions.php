<?php

namespace ValerioMonti\AutoAltText\App\Admin;

use OpenAI;
use ValerioMonti\AutoAltText\App\AIProviders\Azure\AzureTranslator;
use ValerioMonti\AutoAltText\App\Exceptions\Azure\AzureTranslateInstanceException;
use ValerioMonti\AutoAltText\App\Logging\FileLogger;
use ValerioMonti\AutoAltText\App\Utilities\AssetsManager;
use ValerioMonti\AutoAltText\App\Utilities\Encryption;
use ValerioMonti\AutoAltText\Config\Constants;

class PluginOptions
{
    private static ?self $instance = null;
    private static AssetsManager $assetsManager;

    private function __construct()
    {

    }

    /**
     * Manage the necessary hooks to implement plugin options and their pages
     * @return void
     */
    public static function register(): void
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        self::$assetsManager = AssetsManager::make();

        add_action('admin_enqueue_scripts', [self::$instance, 'enqueueAdminScripts'], 1);
        add_action('admin_menu', [self::$instance, 'addOptionsPageToTheMenu']);
        add_action('admin_init', [self::$instance, 'setupPluginOptions']);

        // Encrypt API Keys on update
        add_action('pre_update_option_' . Constants::AAT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION, [self::$instance, 'encryptDataOnUpdate'], 10, 3);
        add_action('pre_update_option_' . Constants::AAT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE, [self::$instance, 'encryptDataOnUpdate'], 10, 3);
        add_action('pre_update_option_' . Constants::AAT_OPTION_FIELD_API_KEY_OPENAI, [self::$instance, 'encryptDataOnUpdate'], 10, 3);
    }

    /**
     * Encrypt data
     * @param ?string $newValue
     * @param ?string $oldValue
     * @return ?string
     */
    public function encryptDataOnUpdate(?string $newValue, ?string $oldValue): ?string
    {
        if (!empty($newValue)) {
            $newValue = (Encryption::make())->encrypt($newValue);
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

            $adminCss = self::$assetsManager->getAssetUrl('resources/js/admin.js', true);

            wp_enqueue_style(Constants::AAT_PLUGIN_OPTIONS_PAGE_SLUG, $adminCss, [], false);

            if ($isMainOptionsPage) {
                $adminJs = self::$assetsManager->getAssetUrl('resources/js/admin.js', false);
                wp_enqueue_script(Constants::AAT_PLUGIN_OPTIONS_PAGE_SLUG, $adminJs, [], false);
            }

        }
    }

    /**
     * Create options pages
     * @return void
     */
    public static function addOptionsPageToTheMenu(): void
    {
        add_menu_page('Auto Alt Text Options', 'Auto Alt Text', 'manage_options', Constants::AAT_PLUGIN_OPTIONS_PAGE_SLUG, [self::$instance, 'optionsMainPage'], null, 99);
        add_submenu_page(Constants::AAT_PLUGIN_OPTIONS_PAGE_SLUG, 'Error Log', 'Error log', 'manage_options', Constants::AAT_PLUGIN_OPTION_LOG_PAGE_SLUG, [self::$instance, 'logOptionsPage']);
    }

    /**
     * Implement the page showing error log
     * @return void
     */
    public static function logOptionsPage(): void
    {
        $uploadDir = wp_upload_dir();
        $logDir = trailingslashit($uploadDir['basedir']) . Constants::AAT_PLUGIN_SLUG;
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Auto Alt Text Error Log', 'auto-alt-text') ?></h1>
            <div class="aat-options plugin-description">
                <p><?php esc_html_e("On this page you can view the last daily error log generated. The logs from previous days are saved in the folder", 'auto-alt-text'); ?>
                    <strong><?php echo $logDir; ?></strong></p>
                <?php
                $hash = get_option(Constants::AAT_LOG_ASH);
                $logFile = trailingslashit($logDir) . date('Y-m-d') . '-' . $hash . '.log';

                if (!file_exists($logFile)) {
                    $logFile = FileLogger::make(Encryption::make())->findLatestLogFile($logDir);
                }

                if ($logFile && file_exists($logFile)) {
                    $logContent = file_get_contents($logFile);
                    echo '<textarea id="error-log" name="error-log" readonly>' . esc_html($logContent) . '</textarea>';
                } else {
                    echo '<p>' . esc_html__('There is no log file yet!', 'auto-alt-text') . '</p>';
                }

                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Implement the main option page
     * @return void
     */
    public static function optionsMainPage(): void
    {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Auto Alt Text Options', 'auto-alt-text') ?></h1>

            <div class="aat-options plugin-description">
                <p>
                    <?php esc_html_e("This plugin allows you to automatically generate Alt Text for the images that are uploaded to the site's media library.", 'auto-alt-text'); ?>
                    <br>
                    <?php esc_html_e("The following methods are available to generate the alt text:", 'auto-alt-text'); ?>
                </p>
                <ul>
                    <li>
                        <strong><?php esc_html_e("Azure's APIs", 'auto-alt-text'); ?></strong>: <?php esc_html_e("the image will be analyzed by the AI services provided by Azure and an alt text will be generated in the language of your choice;", 'auto-alt-text'); ?>
                    </li>
                    <li>
                        <strong><?php esc_html_e("OpenAI's APIs", 'auto-alt-text'); ?></strong>: <?php esc_html_e("the image will be analyzed by the AI services provided by OpenAI and an alt text will be generated based on the prompt you set;", 'auto-alt-text'); ?>
                    </li>
                    <li>
                        <strong><?php esc_html_e("Title of the article (not AI)", 'auto-alt-text'); ?></strong>: <?php esc_html_e("if the image is uploaded within an article, the title of the article will be used as alt text;", 'auto-alt-text'); ?>
                    </li>
                    <li>
                        <strong><?php esc_html_e("Title of the attachment (not AI)", 'auto-alt-text'); ?></strong>: <?php esc_html_e("the title of the attachment will be copied into the alt text;", 'auto-alt-text'); ?>
                    </li>
                </ul>
                <p><?php esc_html_e("Once all the necessary data for the chosen generation method has been entered, the alt texts will be created automatically upon uploading each image.", 'auto-alt-text'); ?></p>
                <p>
                    <strong><?php esc_html_e('Pay attention please:', 'auto-alt-text') ?></strong> <?php esc_html_e("if the alt text for an image is not generated, check the logs on the", 'auto-alt-text'); ?>
                    <a href="<?php menu_page_url(Constants::AAT_PLUGIN_OPTION_LOG_PAGE_SLUG, true) ?>"><?php esc_html_e('designated page.', 'auto-alt-text') ?></a>
                </p>
            </div>
            <form method="post" action="options.php" class="aat-options">
                <?php
                settings_fields('auto_alt_text_options');

                echo '<div>';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_TYPOLOGY . '">' . esc_html__('Generation method', 'auto-alt-text') . '</label>';
                echo '<p class="description">' . esc_html__("Which method do you want to use to generate the alt text for the images?", 'auto-alt-text') . '</p>';
                $typology = get_option(Constants::AAT_OPTION_FIELD_TYPOLOGY);
                ?>
                <select name="<?php echo Constants::AAT_OPTION_FIELD_TYPOLOGY; ?>"
                        id="<?php echo Constants::AAT_OPTION_FIELD_TYPOLOGY; ?>">
                    <option value="<?php echo Constants::AAT_OPTION_TYPOLOGY_DEACTIVATED; ?>"<?php echo self::selected($typology, Constants::AAT_OPTION_TYPOLOGY_DEACTIVATED); ?>><?php esc_html_e("Deactivated", 'auto-alt-text'); ?></option>
                    <option value="<?php echo Constants::AAT_OPTION_TYPOLOGY_CHOICE_AZURE; ?>"<?php echo self::selected($typology, Constants::AAT_OPTION_TYPOLOGY_CHOICE_AZURE); ?>><?php esc_html_e("Azure's APIs", 'auto-alt-text'); ?></option>
                    <option value="<?php echo Constants::AAT_OPTION_TYPOLOGY_CHOICE_OPENAI; ?>"<?php echo self::selected($typology, Constants::AAT_OPTION_TYPOLOGY_CHOICE_OPENAI); ?>><?php esc_html_e("OpenAI's APIs", 'auto-alt-text'); ?></option>
                    <option value="<?php echo Constants::AAT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE; ?>"<?php echo self::selected($typology, Constants::AAT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE); ?>><?php esc_html_e("Title of the article (not AI)", 'auto-alt-text'); ?></option>
                    <option value="<?php echo Constants::AAT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE; ?>"<?php echo self::selected($typology, Constants::AAT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE); ?>><?php esc_html_e("Title of the attachment (not AI)", 'auto-alt-text'); ?></option>
                </select>
                <?php
                echo '</div>';

                echo '<div class="plugin-option type-article-title"><strong>' . esc_html__('Notice', 'auto-alt-text') . '</strong>: ' .
                    esc_html__('If you try to insert an image into a post that has not yet been saved as a draft or published, the plugin cannot generate an alt text based on the post\'s title since the title itself has not yet been saved.', 'auto-alt-text') . ' ' .
                    esc_html__('Therefore, the alt text "Auto draft" will be inserted. To avoid this behavior, save the article draft first and then upload the image.', 'auto-alt-text') .
                    '</div>';


                echo '<div class="plugin-option type-openai"><strong>' . esc_html__('Notice', 'auto-alt-text') . '</strong>: ' .
                    esc_html__('This plugin leverages the new "gpt-4-vision-preview" model from OpenAI to identify the content of the image.', 'auto-alt-text') . ' ' .
                    esc_html__('As the name suggests, this model is still in a preview stage and OpenAI states:', 'auto-alt-text') . ' "<em>This is a preview model version and not suited yet for production traffic</em>".<br>' .
                    esc_html__('Therefore, it is necessary to select a fallback model in case gpt-4-preview fails.', 'auto-alt-text') . '<br>' .
                    esc_html__('The fallback models are not able to read the content of the image but will rely exclusively on the name of the image file, guessing its content.', 'auto-alt-text') . '<br>' .
                    esc_html__('In case of errors, it is still possible to find the specific reason stated on the', 'auto-alt-text') . ' <a href="' . menu_page_url(Constants::AAT_PLUGIN_OPTION_LOG_PAGE_SLUG, false) . '">' . esc_html__('error log page', 'auto-alt-text') . '</a>.' .
                    '</div>';

                echo '<div class="plugin-option type-openai">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_API_KEY_OPENAI . '">' . esc_html__('OpenAI API Key', 'auto-alt-text') . '</label>';
                echo '<p class="description">' . esc_html__("Enter your API Key", 'auto-alt-text') . '</p>';
                $apiKey = get_option(Constants::AAT_OPTION_FIELD_API_KEY_OPENAI);
                echo '<input type="password" name="' . Constants::AAT_OPTION_FIELD_API_KEY_OPENAI . '" value="' . (Encryption::make())->decrypt($apiKey) . '" />';
                echo '</div>';

                echo '<div class="plugin-option type-openai">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_PROMPT_OPENAI . '">' . esc_html__('Prompt', 'auto-alt-text') . '</label>';
                echo '<p class="description">' . esc_html__("Enter a specific and detailed prompt according to your needs.", 'auto-alt-text') . '</p>';
                $defaultPrompt = sprintf(esc_html__("Act like an SEO expert and write an alt text of up to 125 characters for this image.", 'auto-alt-text'), Constants::AAT_IMAGE_URL_TAG);
                $prompt = get_option(Constants::AAT_OPTION_FIELD_PROMPT_OPENAI) ?: $defaultPrompt;
                echo '<textarea name="' . Constants::AAT_OPTION_FIELD_PROMPT_OPENAI . '" rows="5" cols="50">' . $prompt . '</textarea>';
                echo '</div>';

                echo '<div class="plugin-option type-openai">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_FALLBACK_MODEL_OPENAI . '">' . esc_html__('Fallback OpenAi Model', 'auto-alt-text') . '</label>';
                echo '<p class="description">' . esc_html__("Choose the alternative OpenAI model you want to use to generate the alt text when the gpt-4-vision-preview model fails.", 'auto-alt-text') . '</p>';
                $modelSaved = get_option(Constants::AAT_OPTION_FIELD_FALLBACK_MODEL_OPENAI);
                ?>

                <select name="<?php echo Constants::AAT_OPTION_FIELD_FALLBACK_MODEL_OPENAI; ?>"
                        id="<?php echo Constants::AAT_OPTION_FIELD_FALLBACK_MODEL_OPENAI; ?>">
                    <?php
                    foreach (Constants::AAT_OPENAI_MODELS as $modelName) :
                        ?>
                        <option value="<?php echo $modelName; ?>" <?php echo self::isModelSelected($modelSaved, $modelName) ? 'selected="selected"' : ''; ?>><?php echo $modelName; ?></option>
                    <?php
                    endforeach;
                    ?>
                </select>

                <?php
                echo '</div>';

                echo '<div class="plugin-option type-openai">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_FALLBACK_PROMPT_OPENAI . '">' . esc_html__('Fallback Prompt', 'auto-alt-text') . '</label>';
                echo '<p class="description">' . esc_html__("Enter a specific and detailed prompt according to your needs.", 'auto-alt-text') . '</p>';
                $defaultPrompt = sprintf(esc_html__("Act like an SEO expert and write an English alt text for this image %s, using a maximum of 125 characters. Just return the text without any additional comments.", 'auto-alt-text'), Constants::AAT_IMAGE_URL_TAG);
                $fallbackPrompt = get_option(Constants::AAT_OPTION_FIELD_FALLBACK_PROMPT_OPENAI) ?: $defaultPrompt;
                echo '<textarea name="' . Constants::AAT_OPTION_FIELD_FALLBACK_PROMPT_OPENAI . '" rows="5" cols="50">' . $fallbackPrompt . '</textarea>';
                echo '</div>';

                echo '<div class="plugin-option type-azure">' . esc_html__("Fill out the following fields to leverage Azure's computer vision services to generate the Alt texts.", 'auto-alt-text') . '</div>';

                echo '<div class="plugin-option type-azure">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION . '">' . esc_html__('Azure Computer Vision API Key', 'auto-alt-text') . '</label>';
                echo '<p class="description">' . esc_html__("Enter the API key for the Computer Vision service of your Azure account.", 'auto-alt-text') . '</p>';
                $apiKey = get_option(Constants::AAT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION);
                echo '<input type="password" name="' . Constants::AAT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION . '" value="' . (Encryption::make())->decrypt($apiKey) . '" />';
                echo '</div>';

                echo '<div class="plugin-option type-azure">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_COMPUTER_VISION . '">' . esc_html__('Azure Computer Vision Endpoint', 'auto-alt-text') . '</label>';
                echo '<p class="description">' . esc_html__("Enter the endpoint of the Computer Vision service.", 'auto-alt-text') . ' (es. https://computer-vision-france-central.cognitiveservices.azure.com/)</p>';
                $endpoint = get_option(Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_COMPUTER_VISION);
                echo '<input type="text" name="' . Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_COMPUTER_VISION . '" value="' . $endpoint . '" />';
                echo '</div>';

                echo '<div class="plugin-option type-azure">' .
                    '<strong>' . esc_html__('The default alt text language is English.', 'auto-alt-text') . '</strong><br>' .
                    esc_html__('If you want to translate into another language, enter the following data necessary for the translation API to work.', 'auto-alt-text') . ' ' .
                    esc_html__('After saving the changes you can select the desired language.', 'auto-alt-text') .

                    '</div>';

                echo '<div class="plugin-option type-azure">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE . '">' . esc_html__('Azure Translate Instance API Key', 'auto-alt-text') . '</label>';
                echo '<p class="description">' . esc_html__("Enter your API key for the Azure Translate Instance service.", 'auto-alt-text') . '</p>';
                $translationApiKey = get_option(Constants::AAT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE);
                echo '<input type="password" name="' . Constants::AAT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE . '" value="' . (Encryption::make())->decrypt($translationApiKey) . '" class="notRequired" />';
                echo '</div>';

                echo '<div class="plugin-option type-azure">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_TRANSLATE_INSTANCE . '">' . esc_html__('Azure Translate Instance Endpoint', 'auto-alt-text') . '</label>';
                echo '<p class="description">' . esc_html__("Enter the endpoint of the Translate Instance service", 'auto-alt-text') . ' (es. https://api.cognitive.microsofttranslator.com/)</p>';
                $translationEndpoint = get_option(Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_TRANSLATE_INSTANCE);
                echo '<input type="text" name="' . Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_TRANSLATE_INSTANCE . '" value="' . $translationEndpoint . '" class="notRequired" />';
                echo '</div>';

                echo '<div class="plugin-option type-azure">';
                echo '<label for="' . Constants::AAT_OPTION_FIELD_REGION_AZURE_TRANSLATE_INSTANCE . '">' . esc_html__('Azure Translate Instance Region', 'auto-alt-text') . '</label>';
                echo '<p class="description">' . esc_html__("Enter the region of the Azure Translate Instance service.", 'auto-alt-text') . ' (es. westeurope)</p>';
                $translationRegion = get_option(Constants::AAT_OPTION_FIELD_REGION_AZURE_TRANSLATE_INSTANCE);
                echo '<input type="text" name="' . Constants::AAT_OPTION_FIELD_REGION_AZURE_TRANSLATE_INSTANCE . '" value="' . $translationRegion . '" class="notRequired" />';
                echo '</div>';

                if ($translationApiKey && $translationEndpoint && $translationRegion):
                    echo '<div class="plugin-option type-azure">';
                    echo '<label for="' . Constants::AAT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE . '">' . esc_html__('Alt Text Language', 'auto-alt-text') . '</label>';
                    echo '<p class="description">' . esc_html__("Select the language in which the alt text should be written.", 'auto-alt-text') . '</p>';
                    $currentLanguage = get_option(Constants::AAT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE);

                    try {
                        $supportedLanguages = (AzureTranslator::make())->supportedLanguages();
                    } catch (AzureTranslateInstanceException $e) {
                        $supportedLanguages = [
                            "en" => [
                                "name" => "English",
                                "nativeName" => "English",
                                "dir" => "ltr",
                            ]
                        ];
                        echo '<p style="color:red"><strong>' . $e->getMessage() . '</strong></p>';
                    }

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
                endif;

                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * If option is selected return the selected attribute
     * @param string $selectedValue
     * @param string $inputValue
     * @return string
     */
    public static function selected(string $selectedValue, string $inputValue): string
    {
        if (empty($selectedValue) && 'en' == $inputValue) {
            return ' selected';
        }
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
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_FALLBACK_MODEL_OPENAI, [self::class, 'sanitizeText']);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_FALLBACK_PROMPT_OPENAI, [self::class, 'sanitizeTextArea']);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_COMPUTER_VISION, [self::class, 'sanitizeUrl']);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_ENDPOINT_AZURE_TRANSLATE_INSTANCE, [self::class, 'sanitizeUrl']);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_REGION_AZURE_TRANSLATE_INSTANCE, [self::class, 'sanitizeText']);
        register_setting('auto_alt_text_options', Constants::AAT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE, [self::class, 'sanitizeText']);
    }

    /**
     * Check if a model is selected
     * @param string $modelSaved
     * @param string $currentModel
     * @return bool
     */
    public static function isModelSelected(string $modelSaved, string $currentModel): bool
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
        return (Encryption::make())->decrypt($apiKey);
    }

    /**
     * @return string
     */
    public static function apiKeyAzureComputerVision(): string
    {
        $apiKey = get_option(Constants::AAT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION);
        return (Encryption::make())->decrypt($apiKey);
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
        return (Encryption::make())->decrypt($apiKey);
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
        return get_option(Constants::AAT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE) ?: 'en';
    }

    /**
     * @return string
     */
    public static function model(): string
    {
        return get_option(Constants::AAT_OPTION_FIELD_FALLBACK_MODEL_OPENAI);
    }

    /**
     * @return string
     */
    public static function fallbackPrompt(): string
    {
        return get_option(Constants::AAT_OPTION_FIELD_FALLBACK_PROMPT_OPENAI);
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
