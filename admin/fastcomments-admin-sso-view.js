(function () {
    const disableButton = document.getElementById('fc-sso-disable');
    const disableCancellationButton = document.getElementById('fc-sso-disable-cancel-button');
    const disableConfirmationButton = document.getElementById('fc-sso-disable-confirm-button');
    const noticeSSODisabledSuccess = document.getElementById('sso-disabled-success');
    const noticeSSODisabledFailure = document.getElementById('sso-disabled-failure');

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

    disableButton.addEventListener('click', function() {
        jQuery('#dialog-disable-sso').dialog('open');
    });

    disableCancellationButton.addEventListener('click', function() {
        jQuery('#dialog-disable-sso').dialog('close');
    });

    disableConfirmationButton.addEventListener('click', function() {
        jQuery('#dialog-disable-sso').dialog('close');
        noticeSSODisabledSuccess.classList.add('hidden');
        noticeSSODisabledFailure.classList.add('hidden');

        function onError() {
            noticeSSODisabledFailure.classList.remove('hidden');
        }
        jQuery.ajax({
            url: window.FC_DATA.siteUrl + '/index.php?rest_route=/fastcomments/v1/api/set-sso-enabled&token=' + window.FC_DATA.connectionToken,
            method: 'POST',
            dataType: 'json',
            data: {
                'is-enabled': false
            },
            success: function success(response) {
                if (response && response.status === 'success') {
                    noticeSSODisabledSuccess.classList.remove('hidden');
                }
                else {
                    onError();
                }
            },
            error: onError
        });
    });

})();
