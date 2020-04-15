<div id="fastcomments-admin">
    <a class="logo" href="https://fastcomments.com" target="_blank">
        <img src="<?php echo plugin_dir_url(dirname(__FILE__)); ?>/admin/images/logo.png" alt="FastComments Logo"
             title="FastComments Logo">
        <span class="text">FastComments.com</span>
    </a>
    <h3>FastComments Single-Sign-On</h3>
    <?php if (get_option('fastcomments_sso_enabled')) { ?>
        <?php if (get_option('users_can_register')) { ?>
            <p>
                <!-- TODO show ability to disable -->
                <!-- TODO confirmation -->
                <!-- TODO actually disable -->
            </p>
        <?php } else { ?>
            <p>
                You're almost there! Before FastComments SSO can work single-sign-on WordPress must be configured to let anybody sign up
                on your site.<br>
                You can do that <a href="<?php echo admin_url('options-general.php') ?>">here</a> by enabling "Anyone
                can register" - then come back to this page.
            </p>
        <?php } ?>
    <?php } else { ?>
        <p>
            SSO, or Single-Sign-On, allows you and your users to use accounts on your WordPress site to comment. If you
            aren't already using SSO, <b>some consideration
                should be taken before enabling it.</b><br>
            One of the benefits of FastComments is the frictionless sign up/comment process and SSO adds friction for
            new users as
            they will have to sign up to your WordPress site.
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
                on
                your site.<br>
                You can do that <a href="<?php echo admin_url('options-general.php') ?>">here</a> by enabling "Anyone
                can register" - then come back to this
                page.
            </p>
        <?php } ?>
    <?php } ?>
</div>
