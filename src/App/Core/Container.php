<?php

declare(strict_types=1);

namespace AATXT\App\Core;

use AATXT\App\Admin\MediaLibrary;
use AATXT\App\Admin\PluginOptions;
use AATXT\App\AIProviders\Anthropic\AnthropicResponse;
use AATXT\App\AIProviders\Azure\AzureComputerVisionCaptionsResponse;
use AATXT\App\AIProviders\Azure\AzureTranslator;
use AATXT\App\AIProviders\OpenAI\OpenAIVision;
use AATXT\App\Configuration\AnthropicConfig;
use AATXT\App\Configuration\AzureConfig;
use AATXT\App\Configuration\OpenAIConfig;
use AATXT\App\Infrastructure\Database\ErrorLogSchema;
use AATXT\App\Infrastructure\Http\HttpClientInterface;
use AATXT\App\Infrastructure\Http\WordPressHttpClient;
use AATXT\App\Infrastructure\Repositories\ConfigRepositoryInterface;
use AATXT\App\Infrastructure\Repositories\ErrorLogRepository;
use AATXT\App\Infrastructure\Repositories\ErrorLogRepositoryInterface;
use AATXT\App\Infrastructure\Repositories\WordPressConfigRepository;
use AATXT\App\Logging\DBLogger;
use AATXT\App\Services\AltTextGeneratorFactory;
use AATXT\App\Services\ConfigBasedGeneratorFactory;
use AATXT\App\Services\AltTextService;
use AATXT\App\Utilities\AssetsManager;
use AATXT\App\AIProviders\Decorators\DecoratorBuilder;
use AATXT\App\AltTextGeneratorAi;
use AATXT\App\AltTextGeneratorAttachmentTitle;
use AATXT\App\AltTextGeneratorParentPostTitle;
use AATXT\App\Events\EventDispatcherInterface;
use AATXT\App\Events\SimpleEventDispatcher;
use AATXT\App\Events\AltTextGenerationFailedEvent;
use AATXT\App\Events\Listeners\LogErrorListener;
use AATXT\App\Events\Listeners\NotifyAdminListener;
use AATXT\Config\Constants;
use DI\Container as DIContainer;
use DI\ContainerBuilder;

/**
 * Dependency Injection Container configuration.
 *
 * This class sets up and configures the PHP-DI container with all
 * service bindings for the plugin. It implements the Dependency Inversion
 * Principle by binding interfaces to concrete implementations.
 *
 * Usage:
 * ```php
 * $container = Container::make();
 * $service = $container->get(SomeService::class);
 * ```
 */
final class Container
{
    private static ?DIContainer $instance = null;

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct()
    {
    }

    /**
     * Get or create the container instance.
     *
     * @return DIContainer The configured container
     * @throws \Exception If container build fails
     */
    public static function make(): DIContainer
    {
        if (self::$instance === null) {
            self::$instance = self::build();
        }

        return self::$instance;
    }

    /**
     * Build and configure the container with all service bindings.
     *
     * @return DIContainer The configured container
     * @throws \Exception If container build fails
     */
    private static function build(): DIContainer
    {
        $builder = new ContainerBuilder();

        // Enable compilation for better performance in production
        // Note: Disable in development if you need to modify bindings frequently
        // $builder->enableCompilation(__DIR__ . '/../../../var/cache');

        $builder->addDefinitions(self::getDefinitions());

        return $builder->build();
    }

    /**
     * Get all service definitions for the container.
     *
     * @return array<string, mixed> Array of service definitions
     */
    private static function getDefinitions(): array
    {
        return [
            // WordPress database abstraction
            // Maps wpdb class to the global WordPress database object
            \wpdb::class => function () {
                return $GLOBALS['wpdb'];
            },

            // Database Schema Management
            // Manages error logs table schema
            ErrorLogSchema::class => \DI\create(ErrorLogSchema::class)
                ->constructor(\DI\get(\wpdb::class)),

            // Error Log Repository
            // Maps interface to concrete implementation for error log persistence
            ErrorLogRepositoryInterface::class => \DI\create(ErrorLogRepository::class)
                ->constructor(
                    \DI\get(\wpdb::class),
                    \DI\get(ErrorLogSchema::class)
                ),

            // Config Repository
            // Maps interface to WordPress options implementation for configuration management
            ConfigRepositoryInterface::class => \DI\create(WordPressConfigRepository::class),

            // Database Logger
            // Legacy logger refactored to use repository pattern
            DBLogger::class => \DI\create(DBLogger::class)
                ->constructor(
                    \DI\get(ErrorLogRepositoryInterface::class),
                    \DI\get(ErrorLogSchema::class)
                ),

            // HTTP Client abstraction
            // Maps HttpClientInterface to WordPress HTTP client implementation
            HttpClientInterface::class => \DI\create(WordPressHttpClient::class),

            // OpenAI Configuration
            // Factory that reads configuration from WordPress options
            OpenAIConfig::class => function () {
                return new OpenAIConfig(
                    PluginOptions::apiKeyOpenAI(),
                    PluginOptions::openAiPrompt(),
                    PluginOptions::openAiModel()
                );
            },

            // Anthropic Configuration
            // Factory that reads configuration from WordPress options
            AnthropicConfig::class => function () {
                return new AnthropicConfig(
                    PluginOptions::apiKeyAnthropic(),
                    PluginOptions::anthropicPrompt(),
                    PluginOptions::anthropicModel()
                );
            },

            // Azure Configuration
            // Factory that reads configuration from WordPress options
            // Includes both Computer Vision and Translator settings
            AzureConfig::class => function () {
                return new AzureConfig(
                    PluginOptions::apiKeyAzureComputerVision(),
                    PluginOptions::endpointAzureComputerVision(),
                    '', // Azure doesn't use a model parameter
                    '', // Azure doesn't use a custom prompt
                    PluginOptions::apiKeyAzureTranslateInstance(),
                    PluginOptions::endpointAzureTranslateInstance(),
                    PluginOptions::regionAzureTranslateInstance(),
                    PluginOptions::languageAzureTranslateInstance()
                );
            },

            // OpenAI Vision Provider
            // Automatically injects HttpClientInterface and OpenAIConfig
            OpenAIVision::class => \DI\create(OpenAIVision::class)
                ->constructor(
                    \DI\get(HttpClientInterface::class),
                    \DI\get(OpenAIConfig::class)
                ),

            // Anthropic Claude Provider
            // Automatically injects HttpClientInterface and AnthropicConfig
            AnthropicResponse::class => \DI\create(AnthropicResponse::class)
                ->constructor(
                    \DI\get(HttpClientInterface::class),
                    \DI\get(AnthropicConfig::class)
                ),

            // Azure Translator
            // Automatically injects HttpClientInterface and AzureConfig
            AzureTranslator::class => \DI\create(AzureTranslator::class)
                ->constructor(
                    \DI\get(HttpClientInterface::class),
                    \DI\get(AzureConfig::class)
                ),

            // Azure Computer Vision Provider
            // Automatically injects HttpClientInterface, AzureConfig, and AzureTranslator
            AzureComputerVisionCaptionsResponse::class => \DI\create(AzureComputerVisionCaptionsResponse::class)
                ->constructor(
                    \DI\get(HttpClientInterface::class),
                    \DI\get(AzureConfig::class),
                    \DI\get(AzureTranslator::class)
                ),

            // =============================================
            // Decorated AI Providers (using Decorator Pattern)
            // Order: Provider → Cleaning → Validation → Caching
            // =============================================

            // Decorated OpenAI Vision Provider
            // Applies cleaning and validation decorators
            'openai.vision.decorated' => function ($container) {
                return DecoratorBuilder::wrap($container->get(OpenAIVision::class))
                    ->withCleaning()
                    ->withValidation(false)
                    ->build();
            },

            // Decorated Anthropic Provider
            // Applies cleaning and validation decorators
            'anthropic.decorated' => function ($container) {
                return DecoratorBuilder::wrap($container->get(AnthropicResponse::class))
                    ->withCleaning()
                    ->withValidation(false)
                    ->build();
            },

            // Decorated Azure Provider
            // Applies cleaning and validation decorators
            // Note: Azure has built-in translation, so cleaning is important
            'azure.decorated' => function ($container) {
                return DecoratorBuilder::wrap($container->get(AzureComputerVisionCaptionsResponse::class))
                    ->withCleaning()
                    ->withValidation(false)
                    ->build();
            },

            // Alt Text Generator Factory
            // Factory pattern for creating different types of alt text generators
            // Uses decorated providers for cleaning and validation
            AltTextGeneratorFactory::class => function ($container) {
                $factory = new ConfigBasedGeneratorFactory();

                // Register OpenAI Vision generator (decorated)
                $factory->register(
                    Constants::AATXT_OPTION_TYPOLOGY_CHOICE_OPENAI,
                    function () use ($container) {
                        return AltTextGeneratorAi::make(
                            $container->get('openai.vision.decorated')
                        );
                    }
                );

                // Register Anthropic generator (decorated)
                $factory->register(
                    Constants::AATXT_OPTION_TYPOLOGY_CHOICE_ANTHROPIC,
                    function () use ($container) {
                        return AltTextGeneratorAi::make(
                            $container->get('anthropic.decorated')
                        );
                    }
                );

                // Register Azure generator (decorated)
                $factory->register(
                    Constants::AATXT_OPTION_TYPOLOGY_CHOICE_AZURE,
                    function () use ($container) {
                        return AltTextGeneratorAi::make(
                            $container->get('azure.decorated')
                        );
                    }
                );

                // Register Parent Post Title generator
                $factory->register(
                    Constants::AATXT_OPTION_TYPOLOGY_CHOICE_ARTICLE_TITLE,
                    function () {
                        return AltTextGeneratorParentPostTitle::make();
                    }
                );

                // Register Attachment Title generator
                $factory->register(
                    Constants::AATXT_OPTION_TYPOLOGY_CHOICE_ATTACHMENT_TITLE,
                    function () {
                        return AltTextGeneratorAttachmentTitle::make();
                    }
                );

                return $factory;
            },


            // =============================================
            // Event System (Observer Pattern)
            // =============================================

            // Log Error Listener
            // Listens for AltTextGenerationFailedEvent and logs errors to database
            LogErrorListener::class => function ($container) {
                return new LogErrorListener(
                    $container->get(ErrorLogRepositoryInterface::class)
                );
            },

            // Notify Admin Listener
            // Listens for failure events and can send email notifications
            // Email notifications are disabled by default
            NotifyAdminListener::class => function () {
                return new NotifyAdminListener(
                    false, // Email disabled by default
                    5      // Threshold: 5 failures before notification
                );
            },

            // Event Dispatcher
            // Central event dispatcher with pre-registered listeners
            EventDispatcherInterface::class => function ($container) {
                $dispatcher = new SimpleEventDispatcher();

                // Register LogErrorListener for failure events
                // Note: We're using the listener via event system instead of direct logging
                // This allows for decoupled error handling
                $logErrorListener = $container->get(LogErrorListener::class);
                $dispatcher->listen(
                    AltTextGenerationFailedEvent::class,
                    [$logErrorListener, 'handle']
                );

                // Register NotifyAdminListener for failure events
                $notifyAdminListener = $container->get(NotifyAdminListener::class);
                $dispatcher->listen(
                    AltTextGenerationFailedEvent::class,
                    [$notifyAdminListener, 'handleFailure']
                );

                return $dispatcher;
            },

            // Alt Text Service
            // Main service for generating alt text, uses factory and handles errors
            // Integrated with Event System for decoupled logging
            AltTextService::class => function ($container) {
                return new AltTextService(
                    $container->get(AltTextGeneratorFactory::class),
                    $container->get(ConfigRepositoryInterface::class),
                    $container->get(ErrorLogRepositoryInterface::class),
                    $container->get(EventDispatcherInterface::class)
                );
            },

            // Assets Manager
            // Handles Vite manifest loading for versioned assets
            AssetsManager::class => \DI\create(AssetsManager::class),

            // Media Library
            // Handles media library UI customization and AJAX alt text generation
            MediaLibrary::class => function ($container) {
                return new MediaLibrary(
                    $container->get(AltTextService::class),
                    $container->get(AssetsManager::class)
                );
            },
        ];
    }

    /**
     * Reset the container instance (useful for testing).
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
