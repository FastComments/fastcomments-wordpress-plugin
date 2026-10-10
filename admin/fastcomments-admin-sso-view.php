<?php
wp_enqueue_script('jquery');
wp_enqueue_script('jquery-ui');
wp_enqueue_script('jquery-ui-dialog');
wp_enqueue_style('wp-jquery-ui-dialog');
?>

<div id="fastcomments-admin">
    <a class="logo" href="<?php echo FastCommentsPublic::getSite() ?>" target="_blank">
        <img src="<?php echo plugin_dir_url(dirname(__FILE__)); ?>/admin/images/logo-50.png" alt="FastComments Logo"
             title="FastComments Logo">
        <span class="text">FastComments.com</span>
    </a>
    <a class="fc-back" href="<?php echo admin_url('admin.php?page=fastcomments'); ?>">&larr; Dashboard</a>
    <div class="fc-card">
        <h3>Single Sign-On (SSO)</h3>

        <?php if (!get_option('users_can_register')) { ?>
            <p>
                You're almost there! Note: You don't have the option "users can register" enabled. Assuming you're not
                handling memberships via another plugin, you
                might want to turn that on <a href="<?php echo admin_url('options-general.php') ?>">here</a> by enabling
                "Anyone can register".
            </p>
        <?php } ?>
        <?php if (get_option('fastcomments_sso_enabled')) { ?>
            <div class="notice notice-success is-dismissible hidden" id="sso-disabled-success">
                <p><strong>SSO Disabled! <a
                                href="<?php echo get_admin_url(null, "admin.php?page=fastcomments&sub_page=sso", null) ?>">Refresh</a>.</strong>
                </p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
            <div class="notice notice-error is-dismissible hidden" id="sso-disabled-failure">
                <p><strong>SSO Failed to be disabled! Please refresh the page and try again. If it continues to fail,
                        contact FastComments support.</strong></p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
            <p>SSO, or Single-Sign-On, allows you and your users to use accounts on your WordPress site to comment.</p>
            <p>
                <span class="sso-enabled-badge">&#10003; SSO Enabled</span>
                <button class="button-primary" id="fc-sso-disable">Disable SSO</button>
            </p>

            <div id="dialog-disable-sso" class="hidden">
                <h3>Are you sure?</h3>
                <p>Disabling SSO will mean that users of your blog will use the default FastComments sign up mechanism (they
                    will leave their username/email while commenting).</p>
                <p>Disabling SSO will take effect immediately and any logged in users will have to create a new account the
                    next time they load a page.</p>
                <p class="submit">
                    <button type="button" class="button button-primary" id="fc-sso-disable-confirm-button">Disable SSO Now
                    </button>
                    <button type="button" class="button" id="fc-sso-disable-cancel-button">Cancel</button>
                </p>
            </div>
        <?php } else { ?>
            <div class="notice notice-success is-dismissible hidden" id="sso-enabled-success">
                <p><strong>SSO Enabled! <a
                                href="<?php echo get_admin_url(null, "admin.php?page=fastcomments&sub_page=sso", null) ?>">Refresh</a>.</strong>
                </p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
            <div class="notice notice-error is-dismissible hidden" id="sso-enabled-failure">
                <p><strong>SSO Failed to be enabled! Please refresh the page and try again. If it continues to fail, contact
                        FastComments support.</strong></p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
            <p>
                SSO, or Single-Sign-On, allows you and your users to use accounts on your WordPress site to comment. If you
                aren't already using SSO, <b>some consideration should be taken before enabling it.</b><br>
                One of the benefits of FastComments is the frictionless sign up/comment process and SSO adds friction for
                new users as they will have to sign up to your WordPress site.
            </p>
            <button class="button-primary" id="fc-sso-enable">Enable SSO</button>

            <div id="dialog-enable-sso" class="hidden">
                <h3>Are you sure?</h3>
                <p>Enabling SSO will mean that users of your blog will use your WordPress site to sign in, instead of
                    the default FastComments sign up mechanism (they will <b>not</b> leave their username/email while
                    commenting).</p>
                <p>Enabling SSO will take effect immediately.</p>
                <p class="submit">
                    <button type="button" class="button button-primary" id="fc-sso-enable-confirm-button">Enable SSO
                        Now
                    </button>
                    <button type="button" class="button" id="fc-sso-enable-cancel-button">Cancel</button>
                </p>
            </div>
        <?php } ?>
    </div>
    <?php if (get_option('fastcomments_sso_enabled')) { ?>
        <div class="fc-card">
            <h3>Sync Users</h3>
            <p>
                FastComments creates an account for each of your WordPress users the first time they load a page with
                comments while logged in. Syncing creates those accounts now, and links the comments each user wrote
                before to their account. After that, users are kept up to date on their own: when a user is added or
                edited in WordPress, the change is sent to FastComments right away.
            </p>
            <p id="fc-sso-users-sync-state">
                <?php
                $fc_sso_users_synced_at = get_option('fastcomments_sso_users_synced_at');
                if ($fc_sso_users_synced_at) {
                    echo 'Last synced: ' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $fc_sso_users_synced_at + get_option('gmt_offset') * HOUR_IN_SECONDS)) . '.';
                } else {
                    echo 'Your WordPress users have not been synced yet.';
                }
                ?>
            </p>
            <button class="button-primary" id="fc-sso-sync-users">Sync WordPress Users &rarr; FastComments.com</button>
        </div>
    <?php } ?>

    <div id="dialog-sync-sso-users" class="hidden" style="max-width: 560px;">
        <div class="confirmation">
            <h3>Sync your users?</h3>
            <p id="fc-sso-sync-users-just-enabled" class="hidden">
                <b>SSO is now enabled.</b> Your existing WordPress users can be synced to FastComments now.
            </p>
            <p>
                Syncing creates a FastComments account for each of your WordPress users, the same account they get the
                first time they load a page with comments while logged in. The comments they wrote before, which were
                uploaded to FastComments, are linked to their account.
            </p>
            <p>It will not change or remove any users in your WordPress installation.</p>
            <p>
                Synced users count toward the SSO users of your FastComments plan. On plans billed by monthly active
                users, a synced user only counts once they log in.
            </p>
            <p>After clicking "Yes, sync my users", you must keep this page open for it to complete.</p>
            <p class="submit">
                <button type="button" class="button button-primary" id="fc-sso-sync-users-confirm-button">Yes, sync my users.</button>
                <button type="button" class="button" id="fc-sso-sync-users-cancel-button">Not now</button>
            </p>
        </div>
        <div class="in-progress hidden">
            <p id="fc-sso-sync-users-status-text"></p>
            <p class="submit">
                <button type="button" class="button" id="fc-sso-sync-users-cancel-button-in-progress">Cancel</button>
            </p>
        </div>
    </div>
    <?php
    global $FASTCOMMENTS_VERSION;
    wp_enqueue_script('fastcomments_admin_sso_view', plugin_dir_url(__FILE__) . 'fastcomments-admin-sso-view.js', array(), $FASTCOMMENTS_VERSION);
    ?>
    <?php wp_localize_script('fastcomments_admin_sso_view', 'FC_DATA', array('siteUrl' => get_site_url(), 'nonce' => wp_create_nonce('wp_rest'))); ?>
</div>
