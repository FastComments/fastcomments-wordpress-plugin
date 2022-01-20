(function () {

    const enableButton = document.getElementById('fc-sync-to-wp');
    const enableCancellationButton = document.getElementById('fc-sync-to-wp-cancel-button');
    const enableCancellationButtonInProgress = document.getElementById('fc-sync-to-wp-cancel-button-in-progress');
    const enableConfirmationButton = document.getElementById('fc-sync-to-wp-confirm-button');
    const confirmationArea = document.querySelector('#dialog-sync-to-wp .confirmation');
    const inProgressArea = document.querySelector('#dialog-sync-to-wp .in-progress');
    const inProgressStatusText = document.getElementById('in-progress-status-text');

    jQuery('#dialog-sync-to-wp').dialog({
        title: 'Sync To WordPress Confirmation',
        dialogClass: 'wp-dialog',
        autoOpen: false,
        draggable: false,
        width: 'auto',
        modal: true,
        resizable: true,
        closeOnEscape: true,
        position: {
            my: "center",
            at: "center",
            of: window
        },
        open: function () {
            // close dialog by clicking the overlay behind it
            jQuery('.ui-widget-overlay').bind('click', function () {
                jQuery('#dialog-sync-to-wp').dialog('close');
            });
        },
        create: function () {
            // style fix for WordPress admin
            jQuery('.ui-dialog-titlebar-close').addClass('ui-button');
        },
    });

    enableButton.addEventListener('click', function () {
        jQuery('#dialog-sync-to-wp').dialog('open');
    });

    enableCancellationButton.addEventListener('click', function () {
        jQuery('#dialog-sync-to-wp').dialog('close');
    });

    let cancelled = false;
    enableCancellationButtonInProgress.addEventListener('click', function () {
        cancelled = true;
        jQuery('#dialog-sync-to-wp').dialog('close');
    });

    function centerDialog() {
        jQuery('#dialog-sync-to-wp').dialog('option', 'position', {my: "center", at: "center", of: window});
    }

    enableConfirmationButton.addEventListener('click', function () {
        cancelled = false;
        jQuery('#dialog-sync-to-wp').dialog('option', 'title', 'Downloading Comments to WordPress...');

        confirmationArea.classList.add('hidden');
        inProgressArea.classList.remove('hidden');
        inProgressStatusText.innerHTML = 'Beginning the sync, determining how many comments we need to download...';
        centerDialog();

        function onError() {
            inProgressStatusText.innerHTML = 'Sync failed. Please try again. If this persists, reach out to support.';
            enableCancellationButtonInProgress.innerHTML = 'Close';
            centerDialog();
        }

        let countSoFar = 0;
        let totalCount = 0;

        function onDone() {
            if (cancelled) {
                return;
            }
            jQuery('#dialog-sync-to-wp').dialog('option', 'title', 'Downloaded Comments to WordPress!');
            inProgressStatusText.innerHTML = '✔ Sync complete! Downloaded ' + Number(countSoFar).toLocaleString() + ' of ' + Number(totalCount).toLocaleString() + ' Comments.';
            enableCancellationButtonInProgress.innerHTML = 'Close';
            centerDialog();
        }

        function next(includeCount) {
            if (cancelled) {
                return;
            }
            let url = window.FC_DATA.siteUrl + '/index.php?rest_route=/fastcomments/v1/api/sync-to-wp';
            if (includeCount) {
                url += '&includeCount=true';
            }
            if (countSoFar) {
                url += '&skip=' + countSoFar;
            }
            jQuery.ajax({
                url: url,
                method: 'PUT',
                dataType: 'json',
                success: function success(response) {
                    if (response && response.status === 'success') {
                        if (response.totalCount !== null) {
                            totalCount = response.totalCount;
                        }
                        if (response.count) {
                            countSoFar += response.count;
                        }
                        inProgressStatusText.innerHTML = 'Downloading... Downloaded ' + Number(countSoFar).toLocaleString() + ' out of ' + Number(totalCount).toLocaleString() + ' comments.';
                        if (response.hasMore) {
                            setTimeout(function() {
                                next();
                            }, 100);
                        } else {
                            onDone();
                        }
                    } else {
                        onError();
                    }
                },
                error: onError,
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', window.FC_DATA.nonce);
                }
            });
        }
        next(true);
    });
})();
