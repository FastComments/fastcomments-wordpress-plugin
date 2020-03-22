<div id="fastcomments-admin">
    <h1>Welcome to FastComments.</h1>
    <h3>Let's get you setup.</h3>
    <p>We'll go through a couple steps before FastComments is activated.</p>

    <?php if (!get_option('fastcomments_tenant_id')) { ?>
        <ol>
            <li><input type="checkbox" readonly="readonly" disabled>️ Connect WordPress with FastComments</li>
            <li><input type="checkbox" readonly="readonly" disabled> Sync Your Comments</li>
        </ol>
        <h2>Do you have a FastComments Account?</h2>
        <a class="button-primary"
           href="https://fastcomments.com/wp-sync/?syncId=<?php echo get_option("fastcomments_connection_token") ?>&hasAccount=true"
           target="_blank">Yes</a>
        <a class="button-primary"
           href="https://fastcomments.com/wp-sync/?syncId=<?php echo get_option("fastcomments_connection_token") ?>&hasAccount=false"
           target="_blank">No</a>
    <?php } else if (!get_option('fastcomments_setup')) { ?>
        <ol>
            <li>✔️️ Connect WordPress with FastComments</li>
            <li><input type="checkbox" readonly="readonly" disabled> Sync Your Comments</li>
        </ol>

        <a class="button-primary"
           href="https://fastcomments.com/wp-sync/?syncId=<?php echo get_option("fastcomments_connection_token") ?>&hasAccount=true"
           target="_blank">Re-Run Setup</a>
    <?php } else { ?>
        <!-- This is only here for testing, it should never actually happen due to conditional statements in fastcomments-admin.php before including this file. -->
        <ol>
            <li>✔️ Connect WordPress with FastComments</li>
            <li>✔️ Sync Your Comments</li>
        </ol>

        <a class="button-primary"
           href="https://fastcomments.com/wp-sync/?syncId=<?php echo get_option("fastcomments_connection_token") ?>&hasAccount=true"
           target="_blank">Re-Run Setup</a>
    <?php } ?>

    <script>
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
    </script>
</div>
