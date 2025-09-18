jQuery(document).ready(function($) {
    // Handler for the "Open in GPS" button
    $(document).on('click', '#dame-open-gps', function() {
        const button = $(this);
        const lat = button.data('lat');
        const lng = button.data('lng');

        if (!lat || !lng) {
            return;
        }

        // Check for iOS
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

        let url;
        if (isIOS) {
            // Apple Maps URL scheme
            url = `https://maps.apple.com/?q=${lat},${lng}`;
        } else {
            // Google Maps URL scheme for all other platforms
            url = `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
        }

        window.open(url, '_blank');
    });
});
