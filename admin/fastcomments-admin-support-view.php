<div id="fastcomments-admin">
    <a class="logo" href="<?php echo FastCommentsPublic::getSite() ?>" target="_blank">
        <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>/admin/images/logo-50.png" alt="FastComments Logo" title="FastComments Logo">
        <span class="text">FastComments.com</span>
    </a>
    <a class="fc-back" href="<?php echo admin_url('admin.php?page=fastcomments'); ?>">&larr; Dashboard</a>
    <div class="fc-card">
        <h3>Direct Support</h3>
        <p>Getting support for your FastComments account is simple. Simply go to <a
                    href="<?php echo FastCommentsPublic::getSite() ?>/auth/my-account/help" target="_blank">this page</a> and ask your
            question. You'll need to create an account, which is free, to access the support form.</p>

        <p>If you don't have an account you'll have to pick a plan to sign up, however you won't have to enter credit card information for thirty days.</p>
    </div>

    <div class="fc-card">
        <div class="diagnostics">
            <div class="diagnostic-title">Diagnostic Information</div>
            <div class="diagnostic-info">You should include this with any communications to FastComments representatives.</div>
            <div class="diagnostic-warning">Do not share this information publicly. If you do so, someone may be able to
                take over your account.
                <div>Only share with FastComments representatives.</div>
            </div>
            <textarea readonly class="diagnostic-textarea"><?php global $diagnostic_info; echo json_encode($diagnostic_info, JSON_PRETTY_PRINT); ?></textarea>
        </div>
    </div>
</div>
