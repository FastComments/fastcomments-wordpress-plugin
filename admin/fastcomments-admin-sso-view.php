<?php
wp_enqueue_script('jquery');
wp_enqueue_script('jquery-ui');
wp_enqueue_script('jquery-ui-dialog');
wp_enqueue_style('wp-jquery-ui-dialog');
wp_enqueue_style("fastcomments-admin-sso-view", plugin_dir_url(__FILE__) . 'fastcomments-admin-sso-view.css');
?>

<div id="fastcomments-admin">
    <a class="logo" href="https://fastcomments.com" target="_blank">
        <img src="<?php echo plugin_dir_url(dirname(__FILE__)); ?>/admin/images/logo.png" alt="FastComments Logo"
             title="FastComments Logo">
        <span class="text">FastComments.com</span>
    </a>
    <h3>FastComments Single-Sign-On</h3>
    <?php if (get_option('fastcomments_sso_enabled')) { ?>
        <?php if (get_option('users_can_register')) { ?>
            <div class="notice notice-success is-dismissible hidden" id="sso-disabled-success">
                <p><strong>SSO Disabled!</strong></p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
            <div class="notice notice-error is-dismissible hidden" id="sso-disabled-failure">
                <p><strong>SSO Failed to be disabled! Please refresh the page and try again. If it continues to fail, contact FastComments support.</strong></p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
            <p>SSO, or Single-Sign-On, allows you and your users to use accounts on your WordPress site to comment.</p>
            <span class="sso-enabled-badge">✔️ SSO Enabled</span>
            <button class="button-primary" id="fc-sso-disable">Disable SSO</button>

            <div id="dialog-disable-sso" class="hidden">
                <h3>Are you sure?</h3>
                <p>Disabling SSO will mean that users of your blog will use the default FastComments sign up mechanism (they will leave their username/email while commenting).</p>
                <p>Disabling SSO will take effect immediately and any logged in users will have to create a new account the next time they load a page.</p>
                <p class="submit">
                    <button type="button" class="button button-primary" id="fc-sso-disable-confirm-button">Disable SSO Now</button>
                    <button type="button" class="button" id="fc-sso-disable-cancel-button">Cancel</button>
                </p>
            </div>
        <?php } else { ?>
            <p>
                You're almost there! Before FastComments SSO can work single-sign-on WordPress must be configured to let
                anybody sign up on your site.<br>
                You can do that <a href="<?php echo admin_url('options-general.php') ?>">here</a> by enabling "Anyone
                can register" - then come back to this page.
            </p>
        <?php } ?>
    <?php } else { ?>
        <p>
            SSO, or Single-Sign-On, allows you and your users to use accounts on your WordPress site to comment. If you
            aren't already using SSO, <b>some consideration should be taken before enabling it.</b><br>
            One of the benefits of FastComments is the frictionless sign up/comment process and SSO adds friction for
            new users as they will have to sign up to your WordPress site.
        </p>
        <?php if (get_option('users_can_register')) { ?>
            <p>
                <!-- TODO show button
                TODO button goes to FC website, WP SSO setup process
                TODO FC site calls WP w/ sso token. -->
            </p>
        <?php } else { ?>
            <p>
                You're almost there! Before enabling single-sign-on WordPress must be configured to let anybody sign up
                on your site.<br>
                You can do that <a href="<?php echo admin_url('options-general.php') ?>">here</a> by enabling "Anyone
                can register" - then come back to this page.
            </p>
        <?php } ?>
    <?php } ?>
    <?php wp_enqueue_script('fastcomments_admin_sso_view', plugin_dir_url(__FILE__) . 'fastcomments-admin-sso-view.js', array(), $FASTCOMMENTS_VERSION); ?>
    <?php wp_localize_script('fastcomments_admin_sso_view', 'FC_DATA', array( 'connectionToken' => get_option('fastcomments_connection_token'), 'siteUrl' => get_site_url() )); ?>
</div>
