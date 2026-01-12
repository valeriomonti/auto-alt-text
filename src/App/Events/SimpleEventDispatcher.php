<?php

namespace AATXT\App\Events;

/**
 * Simple implementation of the EventDispatcher interface.
 *
 * This dispatcher maintains an array of listeners indexed by event class
 * and calls them in order when events are dispatched.
 *
 * Example usage:
 * ```php
 * $dispatcher = new SimpleEventDispatcher();
 *
 * // Register a listener
 * $dispatcher->listen(UserCreatedEvent::class, function($event) {
 *     echo "User {$event->getUserId()} was created";
 * });
 *
 * // Dispatch an event
 * $dispatcher->dispatch(new UserCreatedEvent($userId));
 * ```
 *
 * @package AATXT\App\Events
 */
final class SimpleEventDispatcher implements EventDispatcherInterface
{
    /**
     * Registered listeners indexed by event class.
     *
     * @var array<string, array<callable>>
     */
    private $listeners = [];

    /**
     * Dispatch an event to all registered listeners.
     *
     * Listeners are called in the order they were registered.
     * Each listener receives the event object as its argument.
     *
     * @param object $event The event to dispatch
     * @return object The dispatched event (may be modified by listeners)
     */
    public function dispatch(object $event): object
    {
        $eventClass = get_class($event);

        // Call listeners registered for the exact class
        if (isset($this->listeners[$eventClass])) {
            foreach ($this->listeners[$eventClass] as $listener) {
                $listener($event);
            }
        }

        // Also call listeners registered for parent classes/interfaces
        foreach ($this->listeners as $registeredClass => $listeners) {
            if ($registeredClass !== $eventClass && $event instanceof $registeredClass) {
                foreach ($listeners as $listener) {
                    $listener($event);
                }
            }
        }

        return $event;
    }

    /**
     * Register a listener for a specific event class.
     *
     * @param string $eventClass The event class to listen for
     * @param callable $listener The listener callback
     * @return void
     */
    public function listen(string $eventClass, callable $listener): void
    {
        if (!isset($this->listeners[$eventClass])) {
            $this->listeners[$eventClass] = [];
        }

        $this->listeners[$eventClass][] = $listener;
    }

    /**
     * Remove a listener for a specific event class.
     *
     * Note: This only works for listeners that can be compared with ===.
     * Anonymous functions are only equal if they are the same instance.
     *
     * @param string $eventClass The event class
     * @param callable $listener The listener to remove
     * @return bool True if the listener was found and removed
     */
    public function removeListener(string $eventClass, callable $listener): bool
    {
        if (!isset($this->listeners[$eventClass])) {
            return false;
        }

        $key = array_search($listener, $this->listeners[$eventClass], true);

        if ($key === false) {
            return false;
        }

        unset($this->listeners[$eventClass][$key]);
        $this->listeners[$eventClass] = array_values($this->listeners[$eventClass]);

        return true;
    }

    /**
     * Get all listeners for a specific event class.
     *
     * @param string $eventClass The event class
     * @return array<callable> Array of registered listeners
     */
    public function getListeners(string $eventClass): array
    {
        return $this->listeners[$eventClass] ?? [];
    }

    /**
     * Check if there are any listeners for a specific event class.
     *
     * @param string $eventClass The event class
     * @return bool True if there are registered listeners
     */
    public function hasListeners(string $eventClass): bool
    {
        return !empty($this->listeners[$eventClass]);
    }

    /**
     * Remove all listeners for a specific event class.
     *
     * @param string $eventClass The event class
     * @return int The number of listeners removed
     */
    public function clearListeners(string $eventClass): int
    {
        if (!isset($this->listeners[$eventClass])) {
            return 0;
        }

        $count = count($this->listeners[$eventClass]);
        unset($this->listeners[$eventClass]);

        return $count;
    }

    /**
     * Remove all listeners for all event classes.
     *
     * @return void
     */
    public function clearAllListeners(): void
    {
        $this->listeners = [];
    }

    /**
     * Get the count of listeners for a specific event class.
     *
     * @param string $eventClass The event class
     * @return int Number of registered listeners
     */
    public function getListenerCount(string $eventClass): int
    {
        return count($this->listeners[$eventClass] ?? []);
    }
}
