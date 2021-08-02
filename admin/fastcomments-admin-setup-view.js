(function () {
    function getFCConfig(successCB, errorCB) {
        jQuery.ajax({
            url: window.FC_DATA.siteUrl + '/index.php?rest_route=/fastcomments/v1/api/get-config-status',
            method: 'GET',
            dataType: 'json',
            success: successCB,
            error: errorCB,
            xhrFields: {
               withCredentials: true
            }
        })
    }
    function tick(cb) {
        var called = false;
        function next() {
            if (!called) {
                called = true;
                cb();
            }
        }
        jQuery.ajax({
            url: window.FC_DATA.siteUrl + '/index.php?rest_route=/fastcomments/v1/api/tick',
            method: 'GET',
            dataType: 'json',
            success: next,
            error: next,
            xhrFields: {
               withCredentials: true
            }
        })
    }

    var pageLoadedConfig = null;
    getFCConfig(function success(response) {
        if (response.status === 'success') {
            pageLoadedConfig = response.config;
        } else {
            console.error('Could not fetch FastComments configuration', response);
        }
        checkNext();
    }, function error(response) {
        console.error('Could not fetch FastComments configuration', response);
        checkNext();
    });

    // Check for setup being complete every couple seconds and then reload the page when it is to show the new admin page with all the fancy options.
    function checkNext() {
        function tickAndCheckNext() {
            tick(function() {
                setTimeout(checkNext, 2000);
            });
        }
        getFCConfig(function success(response) {
            if (response.status === 'success') {
                if (JSON.stringify(pageLoadedConfig) !== JSON.stringify(response.config)) {
                    console.log('FastComments setup changed! Reloading.');
                    window.location.reload();
                } else {
                    console.log('FastComments setup did not change', pageLoadedConfig, response.config);
                    if (response.config.fastcomments_setup === 'setup') {
                        console.log('FastComments is setup, should no longer be on this page. Reloading.', pageLoadedConfig, response.config);
                        window.location.reload();
                    }
                }
            } else {
                console.warn('FastComments setup check got unexpected status, will try again.', response);
            }
            tickAndCheckNext();
        }, function error(response) {
            console.error('FastComments setup check got error response, will try again.', response);
            tickAndCheckNext();
        });
    }
})();
