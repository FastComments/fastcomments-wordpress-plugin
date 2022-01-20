<?php
wp_enqueue_script('jquery');
wp_enqueue_script('jquery-ui');
wp_enqueue_script('jquery-ui-dialog');
wp_enqueue_style('wp-jquery-ui-dialog');
?>
<div id="fastcomments-admin">
    <a class="logo" href="https://fastcomments.com" target="_blank">
        <img src="<?php echo plugin_dir_url(dirname(__FILE__)); ?>/admin/images/logo-50.png" alt="FastComments Logo"
             title="FastComments Logo">
        <span class="text">FastComments.com</span>
    </a>
    <h3>Manually Run Comment Sync</h3>
    <noscript>
        <h3>JavaScript is required for this operation.</h3>
    </noscript>

    <p>FastComments automatically keeps your comments synced to your WordPress installation. However, the option exists to re-download all comments from FastComments to WordPress.</p>
    <button class="button-primary" id="fc-sync-to-wp">Run Sync FastComments.com → <b>to</b> → WordPress.</button>

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
            <p id="in-progress-status-text"></p>
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
