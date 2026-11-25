<?php

namespace AATXT\App\Admin;

use AATXT\App\Services\AltTextService;
use AATXT\App\Utilities\AssetsManager;
use AATXT\Config\Constants;

class MediaLibrary
{
    /**
     * Alt text generation service
     *
     * @var AltTextService
     */
    private $altTextService;

    /**
     * Assets manager for handling Vite manifests
     *
     * @var AssetsManager
     */
    private $assetsManager;

    /**
     * Constructor
     *
     * @param AltTextService $altTextService Service for generating alt text
     * @param AssetsManager $assetsManager Manager for asset URLs
     */
    public function __construct(AltTextService $altTextService, AssetsManager $assetsManager)
    {
        $this->altTextService = $altTextService;
        $this->assetsManager = $assetsManager;
    }

    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue'], 1);

        // Render custom template in media modal
        add_action('print_media_templates', [$this, 'renderGenerateButtonTemplate']);

        // Add button to generate alt text in media library
        add_filter('attachment_fields_to_edit', [$this, 'addGenerateAltTextButton'], 10, 2);

        // Register REST route to generate alt text
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
    }

    public function enqueue(): void
    {
        $screen = get_current_screen();

        // Load script in Media Library and in any post editing/modal (all CPTs)
        if (! $screen || in_array($screen->base, ['upload', 'post'], true)) {
            $mediaLibraryJs = $this->assetsManager->getAssetUrl('resources/js/media-library.js', false);
            wp_enqueue_script(
                Constants::AATXT_PLUGIN_MEDIA_LIBRARY_HANDLE,
                $mediaLibraryJs,
                ['jquery'],
                false,
                true
            );

            wp_localize_script(
                Constants::AATXT_PLUGIN_MEDIA_LIBRARY_HANDLE,
                'AATXT',
                [
                    'restNonce' => wp_create_nonce('wp_rest'),
                    'restUrl'   => esc_url_raw(
                        rest_url(Constants::AATXT_REST_NAMESPACE . Constants::AATXT_REST_ROUTE_GENERATE_ALT_TEXT)
                    ),
                ]
            );
        }
    }

    public function renderGenerateButtonTemplate(): void
    {
        ?>
        <script type="text/html" id="tmpl-aatxt-generate-alt-text">
            <# if ( data.type === 'image' ) { #>
            <button class="button aatxt-generate-alt-text" data-post-id="{{ data.id }}">
                <?php esc_html_e('Generate Alt Text', 'auto-alt-text'); ?>
            </button>
            <span class="spinner"></span>
            <# } #>
        </script>

        <?php
    }

    public function addGenerateAltTextButton(array $form_fields, \WP_Post $post): array
    {
        if (! wp_attachment_is_image($post->ID)) {
            return $form_fields;
        }

        $mimeType = get_post_mime_type($post->ID);
        $altTextGenerationTypology = PluginOptions::typology();

        if ($altTextGenerationTypology === Constants::AATXT_OPTION_TYPOLOGY_CHOICE_OPENAI
            && ! in_array($mimeType, Constants::AATXT_OPENAI_ALLOWED_MIME_TYPES, true)
        ) {
            return $form_fields;
        }

        if ($altTextGenerationTypology === Constants::AATXT_OPTION_TYPOLOGY_CHOICE_AZURE
            && ! in_array($mimeType, Constants::AATXT_AZURE_ALLOWED_MIME_TYPES, true)
        ) {
            return $form_fields;
        }

        $form_fields['generate_alt_text'] = [
            'label' => get_post_mime_type($post->ID),
            'input' => 'html',
            'html'  => '<button type="button" class="button" id="generate-alt-text-button" data-post-id="' . $post->ID . '">'
                . esc_html__('Generate Alt Text', 'auto-alt-text') .
                '</button><span id="loading-spinner" class="spinner" style="float:none; margin-left:5px; display:none;"></span>',
            'helps' => '',
        ];

        return $form_fields;
    }

    /**
     * Register the REST API routes handled by this class.
     *
     * @return void
     */
    public function registerRestRoutes(): void
    {
        register_rest_route(
            Constants::AATXT_REST_NAMESPACE,
            Constants::AATXT_REST_ROUTE_GENERATE_ALT_TEXT,
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'generateAltText'],
                'permission_callback' => [$this, 'canGenerateAltText'],
                'args'                => [
                    'post_id' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                        'validate_callback' => static function ($value): bool {
                            return absint($value) > 0;
                        },
                    ],
                ],
            ]
        );
    }

    /**
     * Permission check for the alt text generation endpoint.
     *
     * @param \WP_REST_Request<array<string, mixed>> $request The REST request.
     * @return bool True if the current user may generate alt text.
     */
    public function canGenerateAltText(\WP_REST_Request $request): bool
    {
        $postId = absint($request->get_param('post_id'));

        return $postId > 0 && current_user_can('edit_post', $postId);
    }

    /**
     * Generate alt text for an attachment.
     *
     * @param \WP_REST_Request<array<string, mixed>> $request The REST request.
     * @return \WP_REST_Response|\WP_Error The generated alt text or an error.
     */
    public function generateAltText(\WP_REST_Request $request)
    {
        $postId = absint($request->get_param('post_id'));

        if (! wp_attachment_is_image($postId)) {
            return new \WP_Error(
                'aatxt_invalid_attachment',
                __('The provided ID is not an image attachment.', 'auto-alt-text'),
                ['status' => 404]
            );
        }

        $mediaUrl = wp_get_attachment_url($postId);
        if (! $mediaUrl) {
            return new \WP_Error(
                'aatxt_media_not_found',
                __('Media not found.', 'auto-alt-text'),
                ['status' => 404]
            );
        }

        $generatedAltText = $this->altTextService->generateForAttachment($postId);

        return new \WP_REST_Response(['alt_text' => $generatedAltText], 200);
    }
}
