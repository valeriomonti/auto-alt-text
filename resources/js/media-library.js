// Wait for the DOM to be fully loaded before executing any script
document.addEventListener('DOMContentLoaded', () => {

    // Delegate click event to the body to handle dynamically loaded buttons
    document.body.addEventListener('click', async (e) => {
        const button = e.target;

        // Only proceed if the clicked element is the correct button
        if (!button || button.id !== 'generate-alt-text-button') return;

        // Extract the post ID from the button's data attributes
        const postId = button.getAttribute('data-post-id');

        // Reference to the spinner element, used to indicate loading state
        const spinner = document.getElementById('loading-spinner');

        // Nonce token provided by WordPress for security
        const nonce = AATXT?.altTextNonce;

        // Set UI to loading state: disable button and show spinner
        toggleUIState(button, spinner, true);

        try {
            // Send a POST request to the WordPress AJAX handler
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'generate_alt_text', // WordPress action hook
                    nonce,
                    post_id: postId
                })
            });

            // Parse the JSON response from the server
            const data = await response.json();

            // If the server indicates success, update the alt text fields
            if (!data.success) {
                console.error('Error generating alt text:', data);
                return;
            }

            updateAltTextFields(data.data.alt_text);

        } catch (error) {
            // Handle any network or JavaScript errors that occur during fetch
            console.error('Error during AJAX request:', error);
        } finally {
            // Restore the UI: enable button and hide spinner
            toggleUIState(button, spinner, false);
        }
    });

    /**
     * Toggles the visual state of the UI between loading and normal.
     * It disables/enables the button and shows/hides the loading spinner.
     *
     * @param {HTMLElement} button - The button that triggered the action
     * @param {HTMLElement|null} spinner - The loading spinner element (if present)
     * @param {boolean} loading - Whether to enable or disable the loading state
     */
    function toggleUIState(button, spinner, loading) {
        button.disabled = loading;
        button.textContent = loading ? 'Generating alt text...' : 'Generate Alt Text';

        if (spinner) {
            spinner.style.display = loading ? 'inline-block' : 'none';
            spinner.classList.toggle('is-active', loading);
        }
    }

    /**
     * Updates the appropriate alt text field in the WordPress Media Library or modal overlay.
     * Triggers a `change` event and invokes WordPress's media `save()` function if available.
     *
     * @param {string} altText - The alt text returned from the server
     */
    function updateAltTextFields(altText) {
        // Check if we are in the media library sidebar
        const uploadField = document.querySelector('.attachment-info .setting.alt-text textarea');

        // Or in the media details overlay
        const overlayField = document.getElementById('attachment-details-alt-text');

        // Use whichever field is available
        const field = uploadField || overlayField;

        if (!field) return;

        // Set the alt text and dispatch a change event to notify WordPress
        field.value = altText;
        field.dispatchEvent(new Event('change', { bubbles: true }));

        // If the WordPress media frame is active, trigger a save
        const content = wp?.media?.frame?.content?.get?.();
        if (typeof content?.save === 'function') {
            content.save();
        }
    }
});
