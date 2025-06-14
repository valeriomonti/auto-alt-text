<?php

namespace AATXT\App\Admin;

use AATXT\App\Logging\DBLogger;
use AATXT\App\AIProviders\Azure\AzureTranslator;
use AATXT\App\Exceptions\Azure\AzureTranslateInstanceException;
use AATXT\App\Utilities\AssetsManager;
use AATXT\App\Utilities\Encryption;
use AATXT\Config\Constants;

class PluginOptions
{
    private static ?self $instance = null;
    private static AssetsManager $assetsManager;

    private function __construct()
    {
        //
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

        add_action('admin_notices', [self::$instance, 'showEncryptionConstantsNotice']);

        add_action('admin_enqueue_scripts', [self::$instance, 'enqueueAdminScripts'], 1);
        add_action('admin_menu', [self::$instance, 'addOptionsPageToTheMenu']);
        add_action('admin_init', [self::$instance, 'setupPluginOptions'], 10);
        add_action('admin_init', [self::$instance, 'migrateEncryptionKeys'], 9);

        // Encrypt API Keys on update
        add_action('pre_update_option_' . Constants::AATXT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION, [self::$instance, 'encryptDataOnUpdate'], 10, 3);
        add_action('pre_update_option_' . Constants::AATXT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE, [self::$instance, 'encryptDataOnUpdate'], 10, 3);
        add_action('pre_update_option_' . Constants::AATXT_OPTION_FIELD_API_KEY_OPENAI, [self::$instance, 'encryptDataOnUpdate'], 10, 3);

        add_action('admin_notices', [self::$instance, 'encryptionErrorNotice']);
    }

    /**
     * Show an error notice if the encryption of the API Key fails
     * @return void
     */
    public function encryptionErrorNotice(): void
    {
        $raw       = get_option(Constants::AATXT_OPTION_FIELD_API_KEY_OPENAI);
        $decrypted = Encryption::make()->decrypt((string) $raw);

        if ($raw && $decrypted === '') {
            $screen = get_current_screen();
            $settingsScreenId = 'toplevel_page_' . Constants::AATXT_PLUGIN_OPTIONS_PAGE_SLUG;
            $showLink = ! ( $screen && $screen->id === $settingsScreenId );

            $settingsUrl = esc_url( menu_page_url(Constants::AATXT_PLUGIN_OPTIONS_PAGE_SLUG, false) );

            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>';

            echo '<strong>' . esc_html__('Auto Alt Text', 'auto-alt-text') . ':</strong> ';

            echo esc_html__(
                'There was a problem with the encryption of your API Key for alt text generation. Please re-enter the key and save.',
                'auto-alt-text'
            );

            if ($showLink) {
                echo ' <a href="' . $settingsUrl . '">'
                    . esc_html__('Go to settings page', 'auto-alt-text')
                    . '</a>.';
            }
            echo '</p>';
            echo '</div>';
        }
    }

    /**
     * Print an admin notice containing the defines to be added
     * to the wp-config.php.
     */
    public function showEncryptionConstantsNotice(): void
    {
        if ( ! is_admin() || ! current_user_can('manage_options') ) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ( ! $screen || $screen->id !== 'toplevel_page_' . Constants::AATXT_PLUGIN_OPTIONS_PAGE_SLUG ) {
            return;
        }

        if ( defined('AATXT_ENCRYPTION_KEY') && defined('AATXT_ENCRYPTION_SALT') ) {
            return;
        }

        $suggestedKey  = bin2hex(random_bytes(32));
        $suggestedSalt = bin2hex(random_bytes(32));
        ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <?php
                printf(
                /* translators: “Optional” label */
                    __( '%s: you can add two lines to your wp-config.php to make your API key more resilient if WordPress salts ever change.', 'auto-alt-text' ),
                    '<strong>' . esc_html__( 'Optional', 'auto-alt-text' ) . '</strong>'
                );
                ?>
            </p>
<pre style="background:#f5f5f5; padding:10px; border-radius:4px;">
define('AATXT_ENCRYPTION_KEY', '<?php echo esc_html( $suggestedKey ); ?>');
define('AATXT_ENCRYPTION_SALT', '<?php echo esc_html( $suggestedSalt ); ?>');
</pre>
            <p>
                <?php esc_html_e(
                    'Just paste them before the line “/* That\'s all, stop editing! Happy publishing. */” in wp-config.php.',
                    'auto-alt-text'
                ); ?>
            </p>
            <p>
                <?php esc_html_e(
                    "If you don't have the ability to edit the wp-config.php, don't worry because the plugin will still work without this change. If your site's salting keys change in the future, you will simply need to resave your API Key in the options below.",
                    "auto-alt-text"
                ); ?>
            </p>
        </div>
        <?php
    }

    public function migrateEncryptionKeys(): void
    {
        if ( ! defined('AATXT_ENCRYPTION_KEY') || ! defined('AATXT_ENCRYPTION_SALT') ) {
            return;
        }

        update_option(Constants::AATXT_LEGACY_ENCRYPTION_MIGRATION_DONE, '0');

        if ( get_option(Constants::AATXT_LEGACY_ENCRYPTION_MIGRATION_DONE) ) {
            return;
        }

        Encryption::make()->migrateLegacyApiKeys();

        update_option(Constants::AATXT_LEGACY_ENCRYPTION_MIGRATION_DONE, '1');
    }

    /**
     * Encrypt data
     * @param ?string $newValue
     * @return ?string
     */
    public function encryptDataOnUpdate(?string $newValue): ?string
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
        $isMainOptionsPage = $screen->id === 'toplevel_page_' . Constants::AATXT_PLUGIN_OPTIONS_PAGE_SLUG;

        if ($isMainOptionsPage || strpos($screen->id, Constants::AATXT_PLUGIN_SLUG . '_') !== false) {

            $adminCss = self::$assetsManager->getAssetUrl('resources/js/admin.js', true);

            wp_enqueue_style(Constants::AATXT_PLUGIN_ASSETS_HANDLE, $adminCss, [], false);

            if ($isMainOptionsPage) {
                $adminJs = self::$assetsManager->getAssetUrl('resources/js/admin.js', false);
                wp_enqueue_script(Constants::AATXT_PLUGIN_ASSETS_HANDLE, $adminJs, [], false);
            }

        }
    }

    /**
     * Create options pages
     * @return void
     */
    public static function addOptionsPageToTheMenu(): void
    {
        add_menu_page('Auto Alt Text Options', 'Auto Alt Text', 'manage_options', Constants::AATXT_PLUGIN_OPTIONS_PAGE_SLUG, [self::$instance, 'optionsMainPage'], null, 99);
        add_submenu_page(Constants::AATXT_PLUGIN_OPTIONS_PAGE_SLUG, 'Error Log', 'Error log', 'manage_options', Constants::AATXT_PLUGIN_OPTION_LOG_PAGE_SLUG, [self::$instance, 'logOptionsPage']);
    }

    /**
     * Implement the page showing error log
     * @return void
     */
    public static function logOptionsPage(): void
    {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Auto Alt Text Error Log', 'auto-alt-text') ?></h1>
            <div class="aat-options plugin-description">
                <p>
                    <?php esc_html_e("If the alt text of your images was not generated correctly, you can find the reason in this log.", 'auto-alt-text'); ?>
                    <br>
                    <?php esc_html_e("Each line corresponds to an error generated by a call to the Azure or OpenAI API after uploading an image.", 'auto-alt-text'); ?>
                </p>
                <?php
                $logs = DBLogger::make()->getImageLog();
                if ($logs) {
                    echo '<textarea id="error-log" name="error-log" readonly>' . esc_textarea($logs) . '</textarea>';
                } else {
                    echo '<p>' . esc_html__('There is no error log yet!', 'auto-alt-text') . '</p>';
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
                        <strong><?php esc_html_e("OpenAI's APIs", 'auto-alt-text'); ?></strong>: <?php esc_html_e("the image will be analyzed by the AI services provided by OpenAI and an alt text will be generated based on the prompt you set;", 'auto-alt-text'); ?>
                    </li>
                    <li>
                        <strong><?php esc_html_e("Azure's APIs", 'auto-alt-text'); ?></strong>: <?php esc_html_e("the image will be analyzed by the AI services provided by Azure and an alt text will be generated in the language of your choice;", 'auto-alt-text'); ?>
                    </li>
                    <li>
                        <strong><?php esc_html_e("Title of the article (not AI)", 'auto-alt-text'); ?></strong>: <?php esc_html_e("if the image is uploaded within an article, the title of the article will be used as alt text;", 'auto-alt-text'); ?>
                    </li>
                    <li>
                        <strong><?php esc_html_e("Title of the attachment (not AI)", 'auto-alt-text'); ?></strong>: <?php esc_html_e("the title of the attachment will be copied into the alt text;", 'auto-alt-text'); ?>
                    </li>
                </ul>
                <p><?php esc_html_e("Once all the necessary data for the chosen generation method has been entered, the alt texts will be created automatically upon uploading each image.", 'auto-alt-text'); ?></p>
                <p><strong>New</strong>: <?php esc_html_e("For images already in the media library, you can create bulk alt texts. Open the Media Library in the \"list\" view, select the images for which to generate the alt text, and choose the \"Generate alt text\" bulk action. (Depending on the number of images chosen and their weight, this may take some time.)", 'auto-alt-text'); ?></p>
                <p>
                    <strong><?php esc_html_e('Pay attention please:', 'auto-alt-text') ?></strong> <?php esc_html_e("if the alt text for an image is not generated, check the logs on the", 'auto-alt-text'); ?>
                    <a href="<?php esc_url(menu_page_url(Constants::AATXT_PLUGIN_OPTION_LOG_PAGE_SLUG, true)); ?>"><?php esc_html_e('designated page.', 'auto-alt-text') ?></a>
                </p>
            </div>
            <form method="post" action="options.php" class="aat-options">
                <?php
                settings_fields('auto_alt_text_options');

                echo '<div>';
                echo '<label for="' .  esc_attr(Constants::AATXT_OPTION_FIELD_TYPOLOGY) . '">' . esc_html__('Generation method', 'auto-alt-text') . '</label>';
                echo '<p class="description">' . esc_html__("Which method do you want to use to generate the alt text for the images?", 'auto-alt-text') . '</p>';
                $typology = self::typology();

                ?>

                <select name="<?php echo esc_attr(Constants::AATXT_OPTION_FIELD_TYPOLOGY); ?>"
                        id="<?php echo esc_attr(Constants::AATXT_OPTION_FIELD_TYPOLOGY); ?>">
                    <option value="<?php echo esc_attr(Constants::AATXT_OPTION_TYPOLOGY_DEACTIVATED); ?>"<?php echo esc_attr(self::selected($typology, Constants::AATXT_OPTION_TYPOLOGY_DEACTIVATED)); ?>><?php esc_html_e("Deactivated", 'auto-alt-text'); ?></option>
                    <option value="<?php echo esc_attr(Constants::AATXT_OPTION_TYPOLOGY_CHOICE_OPENAI); ?>"<?php echo esc_attr(self::selected($typology, Constants::AATXT_OPTION_TYPOLOGY_CHOICE_OPENAI)); ?>><?php esc_html_e("OpenAI's APIs", 'auto-alt-text'); ?></option>
                    <option value="<?php echo esc_attr(Constants::AATXT_OPTION_TYPOLOGY_CHOICE_AZURE); ?>"<?php echo esc_attr(self::selected($typology, Constants::AATXT_OPTION_TYPOLOGY_CHOICE_AZURE)); ?>><?php esc_html_e("Azure's APIs", 'auto-alt-text'); ?></option>
                    <option value="<?php echo esc_attr(Constants::AATXT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE); ?>"<?php echo esc_attr(self::selected($typology, Constants::AATXT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE)); ?>><?php esc_html_e("Title of the article (not AI)", 'auto-alt-text'); ?></option>
                    <option value="<?php echo esc_attr(Constants::AATXT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE); ?>"<?php echo esc_attr(self::selected($typology, Constants::AATXT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE)); ?>><?php esc_html_e("Title of the attachment (not AI)", 'auto-alt-text'); ?></option>
                </select>
                <?php
                echo '</div>';

                echo '<div class="plugin-option type-article-title"><strong>' . esc_html__('Notice', 'auto-alt-text') . '</strong>: ' .
                    esc_html__('If you try to insert an image into a post that has not yet been saved as a draft or published, the plugin cannot generate an alt text based on the post\'s title since the title itself has not yet been saved.', 'auto-alt-text') . ' ' .
                    esc_html__('Therefore, the alt text "Auto draft" will be inserted. To avoid this behavior, save the article draft first and then upload the image.', 'auto-alt-text') .
                    '</div>';

                $openaiModel = self::openAiModel();

                echo '<div class="plugin-option type-openai">';
                echo '<label for="' .  esc_attr(Constants::AATXT_OPTION_FIELD_MODEL_OPENAI) . '">' . esc_html__('Model', 'auto-alt-text') . '</label>';

                echo '<select name="' . esc_attr(Constants::AATXT_OPTION_FIELD_MODEL_OPENAI) . '" id="' . esc_attr(Constants::AATXT_OPTION_FIELD_MODEL_OPENAI) . '">';
                foreach(Constants::AATXT_OPTION_FIELD_MODEL_OPENAI_OPTIONS as $key => $value) {
                    echo '<option value="' . esc_attr($key) . '" ' . esc_attr(self::selected($openaiModel, $key)) . '>' . esc_html($value) . '</option>';
                }
                echo '</select>';
                echo '</div>';

                echo '<div class="plugin-option type-openai"><strong>' . esc_html__('Notice', 'auto-alt-text') . '</strong>: ' .
                    esc_html__('Rarely it may happen that the chosen model fails to generate correct alt text for the image.', 'auto-alt-text') . ' ' .
                    esc_html__('In these cases, the call to the api will be re-performed using the gpt-4o-mini model as fallback.', 'auto-alt-text') . '<br>' .
                    esc_html__('In case of errors, it is still possible to find the specific reason stated on the', 'auto-alt-text') . ' <a href="' . esc_url(menu_page_url(Constants::AATXT_PLUGIN_OPTION_LOG_PAGE_SLUG, false)) . '">' . esc_html__('error log page', 'auto-alt-text') . '</a>.' .
                    '</div>';

                echo '<div class="plugin-option type-openai">';
                echo '<label for="' . esc_attr(Constants::AATXT_OPTION_FIELD_API_KEY_OPENAI) . '">' . esc_html__('OpenAI API Key', 'auto-alt-text') . '</label>';
                echo '<p class="description">' . esc_html__("Enter your API Key", 'auto-alt-text') . '</p>';
                $apiKey = get_option(Constants::AATXT_OPTION_FIELD_API_KEY_OPENAI);
                echo '<input type="password" name="' . esc_attr(Constants::AATXT_OPTION_FIELD_API_KEY_OPENAI) . '" value="' . esc_attr((Encryption::make())->decrypt($apiKey)) . '" />';
                echo '</div>';

                echo '<div class="plugin-option type-openai">';
                echo '<label for="' . esc_attr(Constants::AATXT_OPTION_FIELD_PROMPT_OPENAI) . '">' . esc_html__('Prompt', 'auto-alt-text') . '</label>';
                echo '<p class="description">' . esc_html__("Enter a specific and detailed prompt according to your needs.", 'auto-alt-text') . '</p>';
                $defaultPrompt = sprintf(esc_html__("Act like an SEO expert and write an alt text of up to 125 characters for this image.", 'auto-alt-text'), Constants::AATXT_IMAGE_URL_TAG);
                $prompt = get_option(Constants::AATXT_OPTION_FIELD_PROMPT_OPENAI) ?: $defaultPrompt;
                echo '<textarea name="' . esc_attr(Constants::AATXT_OPTION_FIELD_PROMPT_OPENAI) . '" rows="5" cols="50">' . esc_textarea($prompt) . '</textarea>';
                echo '</div>';

                echo '<div class="plugin-option type-azure">' . esc_html__("Fill out the following fields to leverage Azure's computer vision services to generate the Alt texts.", 'auto-alt-text') . '</div>';

                echo '<div class="plugin-option type-azure">';
                echo '<label for="' . esc_attr(Constants::AATXT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION) . '">' . esc_html__('Azure Computer Vision API Key', 'auto-alt-text') . '</label>';
                echo '<p class="description">' . esc_html__("Enter the API key for the Computer Vision service of your Azure account.", 'auto-alt-text') . '</p>';
                $apiKey = get_option(Constants::AATXT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION);
                echo '<input type="password" name="' . esc_attr(Constants::AATXT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION) . '" value="' . esc_attr((Encryption::make())->decrypt($apiKey)) . '" />';
                echo '</div>';

                echo '<div class="plugin-option type-azure">';
                echo '<label for="' . esc_attr(Constants::AATXT_OPTION_FIELD_ENDPOINT_AZURE_COMPUTER_VISION) . '">' . esc_html__('Azure Computer Vision Endpoint', 'auto-alt-text') . '</label>';
                echo '<p class="description">' . esc_html__("Enter the endpoint of the Computer Vision service.", 'auto-alt-text') . ' (es. https://computer-vision-france-central.cognitiveservices.azure.com/)</p>';
                $endpoint = get_option(Constants::AATXT_OPTION_FIELD_ENDPOINT_AZURE_COMPUTER_VISION);
                echo '<input type="text" name="' . esc_attr(Constants::AATXT_OPTION_FIELD_ENDPOINT_AZURE_COMPUTER_VISION) . '" value="' . esc_attr($endpoint) . '" />';
                echo '</div>';

                echo '<div class="plugin-option type-azure">' .
                    '<strong>' . esc_html__('The default alt text language is English.', 'auto-alt-text') . '</strong><br>' .
                    esc_html__('If you want to translate into another language, enter the following data necessary for the translation API to work.', 'auto-alt-text') . ' ' .
                    esc_html__('After saving the changes you can select the desired language.', 'auto-alt-text') .
                    '</div>';

                echo '<div class="plugin-option type-azure">';
                echo '<label for="' . esc_attr(Constants::AATXT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE) . '">' . esc_html__('Azure Translate Instance API Key', 'auto-alt-text') . '</label>';
                echo '<p class="description">' . esc_html__("Enter your API key for the Azure Translate Instance service.", 'auto-alt-text') . '</p>';
                $translationApiKey = get_option(Constants::AATXT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE);
                echo '<input type="password" name="' . esc_attr(Constants::AATXT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE) . '" value="' . esc_attr((Encryption::make())->decrypt($translationApiKey)) . '" class="notRequired" />';
                echo '</div>';

                echo '<div class="plugin-option type-azure">';
                echo '<label for="' . esc_attr(Constants::AATXT_OPTION_FIELD_ENDPOINT_AZURE_TRANSLATE_INSTANCE) . '">' . esc_html__('Azure Translate Instance Endpoint', 'auto-alt-text') . '</label>';
                echo '<p class="description">' . esc_html__("Enter the endpoint of the Translate Instance service", 'auto-alt-text') . ' (es. https://api.cognitive.microsofttranslator.com/)</p>';
                $translationEndpoint = get_option(Constants::AATXT_OPTION_FIELD_ENDPOINT_AZURE_TRANSLATE_INSTANCE);
                echo '<input type="text" name="' . esc_attr(Constants::AATXT_OPTION_FIELD_ENDPOINT_AZURE_TRANSLATE_INSTANCE) . '" value="' . esc_attr($translationEndpoint) . '" class="notRequired" />';
                echo '</div>';

                echo '<div class="plugin-option type-azure">';
                echo '<label for="' . esc_attr(Constants::AATXT_OPTION_FIELD_REGION_AZURE_TRANSLATE_INSTANCE) . '">' . esc_html__('Azure Translate Instance Region', 'auto-alt-text') . '</label>';
                echo '<p class="description">' . esc_html__("Enter the region of the Azure Translate Instance service.", 'auto-alt-text') . ' (es. westeurope)</p>';
                $translationRegion = get_option(Constants::AATXT_OPTION_FIELD_REGION_AZURE_TRANSLATE_INSTANCE);
                echo '<input type="text" name="' . esc_attr(Constants::AATXT_OPTION_FIELD_REGION_AZURE_TRANSLATE_INSTANCE) . '" value="' . esc_attr($translationRegion) . '" class="notRequired" />';
                echo '</div>';

                if ($translationApiKey && $translationEndpoint && $translationRegion):
                    echo '<div class="plugin-option type-azure">';
                    echo '<label for="' . esc_attr(Constants::AATXT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE) . '">' . esc_html__('Alt Text Language', 'auto-alt-text') . '</label>';
                    echo '<p class="description">' . esc_html__("Select the language in which the alt text should be written.", 'auto-alt-text') . '</p>';
                    $currentLanguage = get_option(Constants::AATXT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE);

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
                        echo '<p style="color:red"><strong>' . esc_html($e->getMessage()) . '</strong></p>';
                    }

                    ?>
                    <select name="<?php echo esc_attr(Constants::AATXT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE); ?>"
                            id="<?php echo esc_attr(Constants::AATXT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE); ?>">
                        <?php
                        foreach ($supportedLanguages as $key => $language):
                            ?>
                            <option value="<?php echo esc_attr($key) ?>" <?php echo esc_attr(self::selected($currentLanguage, $key)); ?>><?php echo esc_html($language['name']); ?></option>
                        <?php
                        endforeach;
                        ?>
                    </select>

                    <?php

                    echo '</div>';
                endif;

                echo '<div class="plugin-option type-article-title type-attachment-title type-openai type-azure">';
                echo '<label for="' . esc_attr(Constants::AATXT_OPTION_FIELD_PRESERVE_EXISTING_ALT_TEXT) . '">' . esc_html__('Keep existing alt text', 'auto-alt-text') . '</label>';
                echo '<p class="description">' . esc_html__("If checked, the existing alt text of images will not be overwritten.", 'auto-alt-text') . '</p>';
                $preserveAltText = get_option(Constants::AATXT_OPTION_FIELD_PRESERVE_EXISTING_ALT_TEXT);
                echo '<input type="checkbox" name="' . esc_attr(Constants::AATXT_OPTION_FIELD_PRESERVE_EXISTING_ALT_TEXT) . '" value="1" class="notRequired" ' . checked(1, $preserveAltText, false) . ' />';
                echo '</div>';

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
        register_setting('auto_alt_text_options', Constants::AATXT_OPTION_FIELD_API_KEY_OPENAI);
        register_setting('auto_alt_text_options', Constants::AATXT_OPTION_FIELD_PROMPT_OPENAI, [self::class, 'sanitizeTextArea']);
        register_setting('auto_alt_text_options', Constants::AATXT_OPTION_FIELD_TYPOLOGY, [self::class, 'sanitizeText']);
        register_setting('auto_alt_text_options', Constants::AATXT_OPTION_FIELD_MODEL_OPENAI, [self::class, 'sanitizeText']);
        register_setting('auto_alt_text_options', Constants::AATXT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION);
        register_setting('auto_alt_text_options', Constants::AATXT_OPTION_FIELD_ENDPOINT_AZURE_COMPUTER_VISION, [self::class, 'sanitizeUrl']);
        register_setting('auto_alt_text_options', Constants::AATXT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE);
        register_setting('auto_alt_text_options', Constants::AATXT_OPTION_FIELD_ENDPOINT_AZURE_TRANSLATE_INSTANCE, [self::class, 'sanitizeUrl']);
        register_setting('auto_alt_text_options', Constants::AATXT_OPTION_FIELD_REGION_AZURE_TRANSLATE_INSTANCE, [self::class, 'sanitizeText']);
        register_setting('auto_alt_text_options', Constants::AATXT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE, [self::class, 'sanitizeText']);
        register_setting('auto_alt_text_options', Constants::AATXT_OPTION_FIELD_PRESERVE_EXISTING_ALT_TEXT);
    }

    /**
     * @return string
     */
    public static function typology(): string
    {
        return get_option(Constants::AATXT_OPTION_FIELD_TYPOLOGY);
    }

    public static function openAiModel(): string
    {
        return get_option(Constants::AATXT_OPTION_FIELD_MODEL_OPENAI) ?: Constants::AATXT_GPT4O;
    }

    /**
     * @return string
     */
    public static function prompt(): string
    {
        return get_option(Constants::AATXT_OPTION_FIELD_PROMPT_OPENAI);
    }

    /**
     * @return string
     */
    public static function apiKeyOpenAI(): string
    {
        $apiKey = get_option(Constants::AATXT_OPTION_FIELD_API_KEY_OPENAI);
        return (Encryption::make())->decrypt($apiKey);
    }

    /**
     * @return string
     */
    public static function apiKeyAzureComputerVision(): string
    {
        $apiKey = get_option(Constants::AATXT_OPTION_FIELD_API_KEY_AZURE_COMPUTER_VISION);
        return (Encryption::make())->decrypt($apiKey);
    }

    /**
     * @return string
     */
    public static function endpointAzureComputerVision(): string
    {
        return get_option(Constants::AATXT_OPTION_FIELD_ENDPOINT_AZURE_COMPUTER_VISION);
    }

    /**
     * @return string
     */
    public static function apiKeyAzureTranslateInstance(): string
    {
        $apiKey = get_option(Constants::AATXT_OPTION_FIELD_API_KEY_AZURE_TRANSLATE_INSTANCE);
        return (Encryption::make())->decrypt($apiKey);
    }

    /**
     * @return string
     */
    public static function endpointAzureTranslateInstance(): string
    {
        return get_option(Constants::AATXT_OPTION_FIELD_ENDPOINT_AZURE_TRANSLATE_INSTANCE);
    }

    /**
     * @return string
     */
    public static function regionAzureTranslateInstance(): string
    {
        return get_option(Constants::AATXT_OPTION_FIELD_REGION_AZURE_TRANSLATE_INSTANCE);
    }

    /**
     * @return string
     */
    public static function languageAzureTranslateInstance(): string
    {
        return get_option(Constants::AATXT_OPTION_FIELD_LANGUAGE_AZURE_TRANSLATE_INSTANCE) ?: 'en';
    }

    /**
     * @return bool
     */
    public static function preserveExistingAltText(): bool
    {
        return get_option(Constants::AATXT_OPTION_FIELD_PRESERVE_EXISTING_ALT_TEXT);
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
