(function () {
    function addNoticeDismissalEventListeners() {
        const noticeDismissButtons = document.querySelectorAll('.notice-dismiss');

        noticeDismissButtons.forEach(function (dismissNoticeButton) {
            dismissNoticeButton.addEventListener('click', function () {
                dismissNoticeButton.parentNode.classList.add('hidden');
            });
        });
    }

    const syncUsersConfirmationArea = document.querySelector('#dialog-sync-sso-users .confirmation');
    const syncUsersInProgressArea = document.querySelector('#dialog-sync-sso-users .in-progress');
    // Each sync gets a number, so a response that arrives after the sync was cancelled or restarted is ignored.
    let syncUsersRun = 0;
    let syncUsersJustEnabled = false;

    // justEnabled: opened as the prompt right after SSO was enabled, instead of from the sync button.
    function openSyncUsersDialog(justEnabled) {
        syncUsersRun++;
        syncUsersJustEnabled = justEnabled;
        document.getElementById('fc-sso-sync-users-just-enabled').classList.toggle('hidden', !justEnabled);
        syncUsersConfirmationArea.classList.remove('hidden');
        syncUsersInProgressArea.classList.add('hidden');
        document.getElementById('fc-sso-sync-users-cancel-button-in-progress').innerHTML = 'Cancel';
        jQuery('#dialog-sync-sso-users').dialog('option', 'title', justEnabled ? 'SSO Enabled' : 'Sync Users Confirmation');
        jQuery('#dialog-sync-sso-users').dialog('open');
    }

    (function syncUsersFlow() {
        const syncButton = document.getElementById('fc-sso-sync-users');
        const cancelButton = document.getElementById('fc-sso-sync-users-cancel-button');
        const cancelButtonInProgress = document.getElementById('fc-sso-sync-users-cancel-button-in-progress');
        const confirmButton = document.getElementById('fc-sso-sync-users-confirm-button');
        const statusText = document.getElementById('fc-sso-sync-users-status-text');
        const syncState = document.getElementById('fc-sso-users-sync-state');

        jQuery('#dialog-sync-sso-users').dialog({
            title: 'Sync Users Confirmation',
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
                    jQuery('#dialog-sync-sso-users').dialog('close');
                });
            },
            close: function () {
                syncUsersRun++;
                if (syncUsersJustEnabled) {
                    // the page was rendered before SSO was enabled, show it in its enabled state
                    window.location.reload();
                }
            },
            create: function () {
                // style fix for WordPress admin
                jQuery('.ui-dialog-titlebar-close').addClass('ui-button');
            },
        });

        // only on the page when SSO is already enabled
        if (syncButton) {
            syncButton.addEventListener('click', function () {
                openSyncUsersDialog(false);
            });
        }

        cancelButton.addEventListener('click', function () {
            jQuery('#dialog-sync-sso-users').dialog('close');
        });

        cancelButtonInProgress.addEventListener('click', function () {
            jQuery('#dialog-sync-sso-users').dialog('close');
        });

        function centerDialog() {
            jQuery('#dialog-sync-sso-users').dialog('option', 'position', {my: "center", at: "center", of: window});
        }

        confirmButton.addEventListener('click', function () {
            const run = ++syncUsersRun;
            jQuery('#dialog-sync-sso-users').dialog('option', 'title', 'Syncing Users to FastComments.com...');

            syncUsersConfirmationArea.classList.add('hidden');
            syncUsersInProgressArea.classList.remove('hidden');
            statusText.textContent = 'Beginning the sync, determining how many users we need to sync...';
            centerDialog();

            let sentCount = 0;
            let createdCount = 0;
            let failedCount = 0;
            let totalCount = 0;

            function syncedOfTotal() {
                return Number(Math.min(sentCount - failedCount, totalCount)).toLocaleString() + ' of ' + Number(totalCount).toLocaleString() + ' users';
            }

            function notSyncedNote() {
                return failedCount > 0 ? ' ' + Number(failedCount).toLocaleString() + ' users could not be synced, the reasons are in your PHP error log.' : '';
            }

            function onEnd(title, text) {
                jQuery('#dialog-sync-sso-users').dialog('option', 'title', title);
                statusText.textContent = text;
                cancelButtonInProgress.innerHTML = 'Close';
                centerDialog();
            }

            function onError() {
                if (run !== syncUsersRun) {
                    return;
                }
                onEnd('Sync Failed', 'Sync failed. Please try again. If this persists, reach out to support.');
            }

            function next(isFirst) {
                let url = window.FC_DATA.siteUrl + '/index.php?rest_route=/fastcomments/v1/api/sync-sso-users';
                if (isFirst) {
                    url += '&includeCount=true';
                    url += '&reset=true';
                }
                jQuery.ajax({
                    url: url,
                    method: 'PUT',
                    dataType: 'json',
                    success: function success(response) {
                        if (run !== syncUsersRun) {
                            return;
                        }
                        if (!response) {
                            return onError();
                        }
                        if (typeof response.totalCount === 'number') {
                            totalCount = response.totalCount;
                        }
                        sentCount += response.count || 0;
                        createdCount += response.createdCount || 0;
                        failedCount += response.failedCount || 0;
                        if (response.status === 'limit-reached') {
                            return onEnd('SSO User Limit Reached', 'The SSO user limit of your FastComments plan was reached after ' + Number(createdCount).toLocaleString() + ' new FastComments accounts were created. The remaining users were not synced.' + notSyncedNote());
                        }
                        if (response.status !== 'success') {
                            return onError();
                        }
                        if (response.hasMore) {
                            statusText.textContent = 'Syncing... Synced ' + syncedOfTotal() + '.';
                            setTimeout(function () {
                                next();
                            }, 100);
                        } else {
                            onEnd('Synced Users to FastComments.com!', '✔ Sync complete! Synced ' + syncedOfTotal() + '. ' + Number(createdCount).toLocaleString() + ' new FastComments accounts were created.' + notSyncedNote());
                            if (syncState) {
                                syncState.textContent = 'Last synced: just now.';
                            }
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
                            openSyncUsersDialog(true);
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
