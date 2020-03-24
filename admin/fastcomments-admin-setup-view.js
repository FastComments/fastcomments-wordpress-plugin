(function () {
    function getFCConfig(successCB, errorCB) {
        jQuery.ajax({
            url: '/index.php?rest_route=/fastcomments/v1/api/get-config&token=<?php echo get_option("fastcomments_connection_token") ?>',
            method: 'GET',
            dataType: 'json',
            success: successCB,
            error: errorCB
        })
    }

    var pageLoadedConfig = null;
    getFCConfig(function success(response) {
        if (response.status === 'success') {
            pageLoadedConfig = response.config;
            checkNext();
        } else {
            console.error('Could not fetch FastComments configuration', response);
        }
    }, function error(response) {
        console.error('Could not fetch FastComments configuration', response);
    });

    // Check for setup being complete every couple seconds and then reload the page when it is to show the new admin page with all the fancy options.
    function checkNext() {
        getFCConfig(function success(response) {
            if (response.status === 'success') {
                if (JSON.stringify(pageLoadedConfig) !== JSON.stringify(response.config)) {
                    console.log('FastComments setup changed! Reloading.');
                    window.location.reload();
                } else {
                    console.log('FastComments setup did not change', pageLoadedConfig, response.config);
                }
            } else {
                console.warn('FastComments setup check got unexpected status, will try again.', response);
            }
            setTimeout(checkNext, 2000);
        }, function error(response) {
            console.error('FastComments setup check got error response, will try again.', response);
            setTimeout(checkNext, 2000);
        });
    }
})();
