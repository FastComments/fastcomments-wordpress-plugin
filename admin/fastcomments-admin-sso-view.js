(function () {
    function addNoticeDismissalEventListeners() {
        const noticeDismissButtons = document.querySelectorAll('.notice-dismiss');

        noticeDismissButtons.forEach(function (dismissNoticeButton) {
            dismissNoticeButton.addEventListener('click', function () {
                dismissNoticeButton.parentNode.classList.add('hidden');
            });
        });
    }

    (function disableSSOFlow() {
        const disableButton = document.getElementById('fc-sso-disable');
        const disableCancellationButton = document.getElementById('fc-sso-disable-cancel-button');
        const disableConfirmationButton = document.getElementById('fc-sso-disable-confirm-button');
        const noticeSSODisabledSuccess = document.getElementById('sso-disabled-success');
        const noticeSSODisabledFailure = document.getElementById('sso-disabled-failure');

        addNoticeDismissalEventListeners();

        if (disableButton) {
            jQuery('#dialog-disable-sso').dialog({
                title: 'Disable SSO Confirmation',
                dialogClass: 'wp-dialog',
                autoOpen: false,
                draggable: false,
                width: 'auto',
                modal: true,
                resizable: false,
                closeOnEscape: true,
                position: {
                    my: "center",
                    at: "center",
                    of: window
                },
                open: function () {
                    // close dialog by clicking the overlay behind it
                    jQuery('.ui-widget-overlay').bind('click', function () {
                        jQuery('#dialog-disable-sso').dialog('close');
                    });
                },
                create: function () {
                    // style fix for WordPress admin
                    jQuery('.ui-dialog-titlebar-close').addClass('ui-button');
                },
            });

            disableButton.addEventListener('click', function () {
                jQuery('#dialog-disable-sso').dialog('open');
            });

            disableCancellationButton.addEventListener('click', function () {
                jQuery('#dialog-disable-sso').dialog('close');
            });

            disableConfirmationButton.addEventListener('click', function () {
                jQuery('#dialog-disable-sso').dialog('close');
                noticeSSODisabledSuccess.classList.add('hidden');
                noticeSSODisabledFailure.classList.add('hidden');

                function onError() {
                    noticeSSODisabledFailure.classList.remove('hidden');
                }

                jQuery.ajax({
                    url: window.FC_DATA.siteUrl + '/index.php?rest_route=/fastcomments/v1/api/set-sso-enabled',
                    method: 'PUT',
                    dataType: 'json',
                    data: {
                        'is-enabled': false
                    },
                    success: function success(response) {
                        if (response && response.status === 'success') {
                            noticeSSODisabledSuccess.classList.remove('hidden');
                        } else {
                            onError();
                        }
                    },
                    error: onError,
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', window.FC_DATA.nonce);
                    }
                });
            });
        }
    })();
    (function enableSSOFlow() {
        const enableButton = document.getElementById('fc-sso-enable');
        const enableCancellationButton = document.getElementById('fc-sso-enable-cancel-button');
        const enableConfirmationButton = document.getElementById('fc-sso-enable-confirm-button');
        const noticeSSOEnabledSuccess = document.getElementById('sso-enabled-success');
        const noticeSSOEnabledFailure = document.getElementById('sso-enabled-failure');

        addNoticeDismissalEventListeners();

        if (enableButton) {
            jQuery('#dialog-enable-sso').dialog({
                title: 'Enable SSO Confirmation',
                dialogClass: 'wp-dialog',
                autoOpen: false,
                draggable: false,
                width: 'auto',
                modal: true,
                resizable: false,
                closeOnEscape: true,
                position: {
                    my: "center",
                    at: "center",
                    of: window
                },
                open: function () {
                    // close dialog by clicking the overlay behind it
                    jQuery('.ui-widget-overlay').bind('click', function () {
                        jQuery('#dialog-enable-sso').dialog('close');
                    });
                },
                create: function () {
                    // style fix for WordPress admin
                    jQuery('.ui-dialog-titlebar-close').addClass('ui-button');
                },
            });

            enableButton.addEventListener('click', function () {
                jQuery('#dialog-enable-sso').dialog('open');
            });

            enableCancellationButton.addEventListener('click', function () {
                jQuery('#dialog-enable-sso').dialog('close');
            });

            enableConfirmationButton.addEventListener('click', function () {
                jQuery('#dialog-enable-sso').dialog('close');
                noticeSSOEnabledSuccess.classList.add('hidden');
                noticeSSOEnabledFailure.classList.add('hidden');

                function onError() {
                    noticeSSOEnabledFailure.classList.remove('hidden');
                }

                jQuery.ajax({
                    url: window.FC_DATA.siteUrl + '/index.php?rest_route=/fastcomments/v1/api/set-sso-enabled',
                    method: 'PUT',
                    dataType: 'json',
                    data: {
                        'is-enabled': true
                    },
                    success: function success(response) {
                        if (response && response.status === 'success') {
                            noticeSSOEnabledSuccess.classList.remove('hidden');
                        } else {
                            onError();
                        }
                    },
                    error: onError,
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', window.FC_DATA.nonce);
                    }
                });
            });
        }
    })();
})();
