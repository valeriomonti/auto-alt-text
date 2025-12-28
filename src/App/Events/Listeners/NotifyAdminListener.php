<?php

namespace AATXT\App\Events\Listeners;

use AATXT\App\Events\AltTextGenerationFailedEvent;
use AATXT\App\Events\BulkActionCompletedEvent;

/**
 * Listener that notifies the admin about significant events.
 *
 * This listener can send email notifications to the WordPress admin
 * when alt text generation fails or when bulk actions complete.
 * Notifications can be enabled/disabled via configuration.
 *
 * @package AATXT\App\Events\Listeners
 */
final class NotifyAdminListener
{
    /**
     * Whether email notifications are enabled
     *
     * @var bool
     */
    private $emailEnabled;

    /**
     * Minimum number of failures before sending notification
     *
     * @var int
     */
    private $failureThreshold;

    /**
     * Counter for failures in current session
     *
     * @var int
     */
    private $failureCount = 0;

    /**
     * Constructor
     *
     * @param bool $emailEnabled Whether to send email notifications
     * @param int $failureThreshold Minimum failures before notification (default: 5)
     */
    public function __construct(bool $emailEnabled = false, int $failureThreshold = 5)
    {
        $this->emailEnabled = $emailEnabled;
        $this->failureThreshold = $failureThreshold;
    }

    /**
     * Handle alt text generation failed event.
     *
     * Increments failure counter and sends notification if threshold is reached.
     *
     * @param AltTextGenerationFailedEvent $event The event to handle
     * @return void
     */
    public function handleFailure(AltTextGenerationFailedEvent $event): void
    {
        $this->failureCount++;

        if (!$this->emailEnabled) {
            return;
        }

        // Send notification when threshold is reached
        if ($this->failureCount === $this->failureThreshold) {
            $this->sendFailureNotification($event);
        }
    }

    /**
     * Handle bulk action completed event.
     *
     * Sends notification if there were failures in the bulk action.
     *
     * @param BulkActionCompletedEvent $event The event to handle
     * @return void
     */
    public function handleBulkAction(BulkActionCompletedEvent $event): void
    {
        if (!$this->emailEnabled) {
            return;
        }

        // Only notify if there were failures
        if ($event->getFailureCount() > 0) {
            $this->sendBulkActionNotification($event);
        }
    }

    /**
     * Send email notification about generation failure.
     *
     * @param AltTextGenerationFailedEvent $event The failure event
     * @return bool Whether the email was sent
     */
    private function sendFailureNotification(AltTextGenerationFailedEvent $event): bool
    {
        if (!function_exists('wp_mail')) {
            return false;
        }

        $adminEmail = $this->getAdminEmail();
        if (empty($adminEmail)) {
            return false;
        }

        $subject = sprintf(
            '[%s] Alt Text Generation Failures Detected',
            $this->getSiteName()
        );

        $message = $this->buildFailureEmailBody($event);

        return wp_mail($adminEmail, $subject, $message);
    }

    /**
     * Send email notification about bulk action results.
     *
     * @param BulkActionCompletedEvent $event The bulk action event
     * @return bool Whether the email was sent
     */
    private function sendBulkActionNotification(BulkActionCompletedEvent $event): bool
    {
        if (!function_exists('wp_mail')) {
            return false;
        }

        $adminEmail = $this->getAdminEmail();
        if (empty($adminEmail)) {
            return false;
        }

        $subject = sprintf(
            '[%s] Bulk Alt Text Generation: %s',
            $this->getSiteName(),
            $event->isFullySuccessful() ? 'Completed' : 'Completed with errors'
        );

        $message = $this->buildBulkActionEmailBody($event);

        return wp_mail($adminEmail, $subject, $message);
    }

    /**
     * Build the email body for failure notifications.
     *
     * @param AltTextGenerationFailedEvent $event The failure event
     * @return string
     */
    private function buildFailureEmailBody(AltTextGenerationFailedEvent $event): string
    {
        $lines = [
            'Alt text generation has been experiencing failures on your site.',
            '',
            'Details:',
            sprintf('- Provider: %s', $event->getProvider()),
            sprintf('- Image ID: %d', $event->getImageId()),
            sprintf('- Error: %s', $event->getErrorMessage()),
            sprintf('- Failures in this session: %d', $this->failureCount),
            '',
            'Please check the Auto Alt Text error log in your WordPress admin.',
            '',
            '---',
            'This notification was sent by the Auto Alt Text plugin.',
        ];

        return implode("\n", $lines);
    }

    /**
     * Build the email body for bulk action notifications.
     *
     * @param BulkActionCompletedEvent $event The bulk action event
     * @return string
     */
    private function buildBulkActionEmailBody(BulkActionCompletedEvent $event): string
    {
        $lines = [
            sprintf('Bulk action "%s" has completed.', $event->getActionName()),
            '',
            'Results:',
            sprintf('- Total items: %d', $event->getTotalItems()),
            sprintf('- Successful: %d', $event->getSuccessCount()),
            sprintf('- Failed: %d', $event->getFailureCount()),
            sprintf('- Success rate: %.1f%%', $event->getSuccessRate()),
            '',
            $event->getSummaryMessage(),
        ];

        if (!$event->isFullySuccessful()) {
            $lines[] = '';
            $lines[] = 'Please check the Auto Alt Text error log for details on failed items.';
        }

        $lines[] = '';
        $lines[] = '---';
        $lines[] = 'This notification was sent by the Auto Alt Text plugin.';

        return implode("\n", $lines);
    }

    /**
     * Get the admin email address.
     *
     * @return string
     */
    private function getAdminEmail(): string
    {
        if (!function_exists('get_option')) {
            return '';
        }

        return get_option('admin_email', '');
    }

    /**
     * Get the site name.
     *
     * @return string
     */
    private function getSiteName(): string
    {
        if (!function_exists('get_option')) {
            return 'WordPress';
        }

        return get_option('blogname', 'WordPress');
    }

    /**
     * Check if email notifications are enabled.
     *
     * @return bool
     */
    public function isEmailEnabled(): bool
    {
        return $this->emailEnabled;
    }

    /**
     * Enable email notifications.
     *
     * @return void
     */
    public function enableEmail(): void
    {
        $this->emailEnabled = true;
    }

    /**
     * Disable email notifications.
     *
     * @return void
     */
    public function disableEmail(): void
    {
        $this->emailEnabled = false;
    }

    /**
     * Get the current failure count.
     *
     * @return int
     */
    public function getFailureCount(): int
    {
        return $this->failureCount;
    }

    /**
     * Reset the failure counter.
     *
     * @return void
     */
    public function resetFailureCount(): void
    {
        $this->failureCount = 0;
    }

    /**
     * Get the events this listener responds to.
     *
     * @return array<string> Array of event class names
     */
    public static function getSubscribedEvents(): array
    {
        return [
            AltTextGenerationFailedEvent::class,
            BulkActionCompletedEvent::class,
        ];
    }
}
