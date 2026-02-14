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
        <h3>Manually Run Comment Sync</h3>
        <noscript>
            <h3>JavaScript is required for this operation.</h3>
        </noscript>

        <p>If you didn't upload your comments during the initial setup, or would like to do it anyway, you can do it now here.</p>
        <button class="button-primary" id="wp-sync-to-fc">Upload WordPress Comments &rarr; FastComments.com</button>

        <p>FastComments automatically keeps your comments synced to your WordPress installation. However, the option exists to re-download all comments from FastComments to WordPress.</p>
        <button class="button-primary" id="fc-sync-to-wp">Download FastComments.com &rarr; WordPress</button>
    </div>

    <div id="dialog-sync-to-fc" class="hidden">
        <div class="confirmation">
            <h3>Are you sure?</h3>
            <p>
                Running the sync from WordPress to FastComments will incrementally **upload** all your comments to your FastComments.com account.
            </p>
            <p>
                It will not remove any comments from your WordPress installation.
            </p>
            <p>
                This is not needed to keep your WordPress install up to date. This is only to upload your comments <b>from</b> WordPress <b>to</b> FastComments.com.
            </p>
            <p>After clicking "Yes, Perform The Sync", you must keep this page open for it to complete.</p>
            <p class="submit">
                <button type="button" class="button button-primary" id="wp-sync-to-fc-confirm-button">Yes, perform the upload.</button>
                <button type="button" class="button" id="wp-sync-to-fc-cancel-button">Cancel</button>
            </p>
        </div>
        <div class="in-progress hidden">
            <p id="wp-sync-to-fc-in-progress-status-text"></p>
            <p class="submit">
                <button type="button" class="button" id="wp-sync-to-fc-cancel-button-in-progress">Cancel</button>
            </p>
        </div>
    </div>

    <div id="dialog-sync-to-wp" class="hidden">
        <div class="confirmation">
            <h3>Are you sure?</h3>
            <p>
                Running the sync from FastComments to WordPress will incrementally download all comments to your WordPress database.
            </p>
            <p>
                It will not remove any comments from your WordPress installation.
            </p>
            <p>
                Unless you are absolutely sure you want to do this, for example your FastComments support representative asked you to perform this action, it is <b>not needed</b> to keep
                WordPress up to date.
            </p>
            <p>After clicking "Yes, Perform The Sync", you must keep this page open for it to complete.</p>
            <p>This will require API credits, depending on the number of comments downloaded.</p>
            <p class="submit">
                <button type="button" class="button button-primary" id="fc-sync-to-wp-confirm-button">Yes, perform the sync.</button>
                <button type="button" class="button" id="fc-sync-to-wp-cancel-button">Cancel</button>
            </p>
        </div>
        <div class="in-progress hidden">
            <p id="fc-sync-to-wp-in-progress-status-text"></p>
            <p class="submit">
                <button type="button" class="button" id="fc-sync-to-wp-cancel-button-in-progress">Cancel</button>
            </p>
        </div>
    </div>
</div>
<?php
global $FASTCOMMENTS_VERSION;
wp_enqueue_script('fastcomments_admin_manual_sync_view', plugin_dir_url(__FILE__) . 'fastcomments-admin-manual-sync-view.js', array(), $FASTCOMMENTS_VERSION);
wp_localize_script('fastcomments_admin_manual_sync_view', 'FC_DATA', array( 'siteUrl' => get_site_url(), 'nonce' => wp_create_nonce('wp_rest') ));
?>
