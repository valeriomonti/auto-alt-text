<?php

namespace AATXT\App\Core;

use AATXT\App\Admin\BulkActions\BulkActionHandler;
use AATXT\App\Admin\BulkActions\GenerateAltTextBulkAction;
use AATXT\App\Admin\MediaLibrary;
use AATXT\App\Infrastructure\Database\ErrorLogSchema;
use AATXT\App\Services\AltTextService;
use DI\Container as DIContainer;

/**
 * Bootstrap class for the Auto Alt Text plugin.
 *
 * This class orchestrates the plugin initialization by:
 * - Setting up the dependency injection container
 * - Registering bulk actions
 * - Initializing the hooks registrar
 *
 * Replaces the Singleton pattern in the old Setup class.
 */
final class PluginBootstrap
{
    /**
     * Dependency injection container
     *
     * @var DIContainer
     */
    private $container;

    /**
     * Path to the main plugin file
     *
     * @var string
     */
    private $pluginFile;

    /**
     * Constructor
     *
     * @param DIContainer $container The DI container
     * @param string $pluginFile Path to the main plugin file
     */
    public function __construct(DIContainer $container, string $pluginFile)
    {
        $this->container = $container;
        $this->pluginFile = $pluginFile;
    }

    /**
     * Boot the plugin.
     *
     * Initializes all plugin components and registers WordPress hooks.
     *
     * @return void
     */
    public function boot(): void
    {
        // Get services from container
        $altTextService = $this->container->get(AltTextService::class);
        $mediaLibrary = $this->container->get(MediaLibrary::class);
        $schema = $this->container->get(ErrorLogSchema::class);

        // Create plugin lifecycle handler
        $lifecycle = new PluginLifecycle($schema);

        // Create and configure bulk action handler
        $bulkActionHandler = $this->createBulkActionHandler($altTextService);

        // Create hooks registrar and register all hooks
        $hooksRegistrar = new HooksRegistrar(
            $altTextService,
            $bulkActionHandler,
            $lifecycle,
            $mediaLibrary,
            $this->pluginFile
        );

        $hooksRegistrar->register();
    }

    /**
     * Create and configure the bulk action handler.
     *
     * @param AltTextService $altTextService The alt text service
     * @return BulkActionHandler Configured bulk action handler
     */
    private function createBulkActionHandler(AltTextService $altTextService): BulkActionHandler
    {
        $handler = new BulkActionHandler();

        // Register the generate alt text bulk action
        $generateAltTextAction = new GenerateAltTextBulkAction($altTextService);
        $handler->register($generateAltTextAction);

        return $handler;
    }

    /**
     * Static factory method for easy plugin initialization.
     *
     * Usage in main plugin file:
     * ```php
     * PluginBootstrap::init(__FILE__);
     * ```
     *
     * @param string $pluginFile Path to the main plugin file
     * @return void
     */
    public static function init(string $pluginFile): void
    {
        $container = Container::make();
        $bootstrap = new self($container, $pluginFile);
        $bootstrap->boot();
    }
}
