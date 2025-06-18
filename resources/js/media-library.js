document.addEventListener('DOMContentLoaded', function() {
    document.body.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'generate-alt-text-button') {
            let postId = e.target.getAttribute('data-post-id');
            let button = e.target;
            let spinner = document.getElementById('loading-spinner');

            // Enable spinner and disable button
            button.disabled = true;
            button.textContent = 'Generazione in corso...';
            if (spinner) {
                spinner.style.display = 'inline-block';
                spinner.classList.add('is-active');
            }

            // AJAX Request to generate Alt Text
            let nonce = AATXT.altTextNonce;
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=generate_alt_text&nonce=' + nonce + '&post_id=' + postId
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        //if we are in media library
                        let uploadAltTextField = document.querySelector('.attachment-info .setting.alt-text textarea');

                        if (uploadAltTextField) {
                            uploadAltTextField.value = data.data.alt_text;

                            let event = new Event('change', {
                                bubbles: true,
                                cancelable: true,
                            });

                            uploadAltTextField.dispatchEvent(event);

                            if (wp && wp.media && wp.media.frame && wp.media.frame.content && wp.media.frame.content.get) {
                                wp.media.frame.content.get().save();
                            }
                        } else {
                            // if we are in article media overlay
                            let attachmentAltTextField = document.getElementById('attachment-details-alt-text');
                            if (attachmentAltTextField) {
                                attachmentAltTextField.value = data.data.alt_text;
                            }

                            attachmentAltTextField.dispatchEvent(new Event('change', { bubbles: true }));

                            if (
                                window.wp &&
                                wp.media &&
                                wp.media.frame &&
                                wp.media.frame.content &&
                                typeof wp.media.frame.content.get === 'function'
                            ) {
                                const content = wp.media.frame.content.get();
                                if (content && typeof content.save === 'function') {
                                    content.save();
                                }
                            }

                        }
                    } else {
                        console.error('Error generating Alt Text', data);
                    }
                })
                .catch(error => {
                    console.error('Error during AJAX Request:', error);
                })
                .finally(() => {
                    // enable button and hide spinner
                    button.disabled = false;
                    button.textContent = 'Generate Alt Text';
                    if (spinner) {
                        spinner.style.display = 'none';
                        spinner.classList.remove('is-active');
                    }
                });
        }
    });
});