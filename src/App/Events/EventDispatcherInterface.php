<?php

namespace AATXT\App\Events;

/**
 * Interface for event dispatching.
 *
 * This interface defines the contract for an event dispatcher that allows
 * decoupled communication between components through events and listeners.
 *
 * The event system follows the Observer pattern, allowing components to:
 * - Dispatch events when something significant happens
 * - Register listeners to react to specific events
 * - Maintain loose coupling between event producers and consumers
 *
 * @package AATXT\App\Events
 */
interface EventDispatcherInterface
{
    /**
     * Dispatch an event to all registered listeners.
     *
     * All listeners registered for the event's class will be called
     * in the order they were registered.
     *
     * @param object $event The event object to dispatch
     * @return object The same event object (allows for event modification by listeners)
     */
    public function dispatch(object $event): object;

    /**
     * Register a listener for a specific event class.
     *
     * The listener will be called whenever an event of the specified class
     * (or a subclass) is dispatched.
     *
     * @param string $eventClass The fully-qualified class name of the event to listen for
     * @param callable $listener The listener callback, receives the event as first argument
     * @return void
     */
    public function listen(string $eventClass, callable $listener): void;

    /**
     * Remove a listener for a specific event class.
     *
     * @param string $eventClass The event class
     * @param callable $listener The listener to remove
     * @return bool True if the listener was found and removed
     */
    public function removeListener(string $eventClass, callable $listener): bool;

    /**
     * Get all listeners for a specific event class.
     *
     * @param string $eventClass The event class
     * @return array<callable> Array of registered listeners
     */
    public function getListeners(string $eventClass): array;

    /**
     * Check if there are any listeners for a specific event class.
     *
     * @param string $eventClass The event class
     * @return bool True if there are registered listeners
     */
    public function hasListeners(string $eventClass): bool;
}
