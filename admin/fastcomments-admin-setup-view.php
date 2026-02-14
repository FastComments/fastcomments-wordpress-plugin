<div id="fastcomments-admin">
    <div class="fc-setup">
        <h1>Welcome to FastComments</h1>
        <h3>Let's get you set up.</h3>
        <p>We'll walk through a couple of steps to activate FastComments on your site.</p>

        <div class="fc-step">
            <?php if (get_option('fastcomments_site') === 'https://eu.fastcomments.com') { ?>
                <h2><span class="fc-step-number">1</span> Where should we store your data?</h2>
                <p>Your data will be stored in the EU.</p>
                <div class="fc-actions">
                    <a class="button-primary fc-btn-secondary button-not-in-eu"
                       href="?page=fastcomments&isEU=false">I'm not in the EU</a>
                </div>
            <?php } else { ?>
                <h2><span class="fc-step-number">1</span> Where should we store your data?</h2>
                <p>By default, your data is replicated globally across all data centers. Want to keep your data exclusively in the EU?</p>
                <div class="fc-actions">
                    <a class="button-primary button-in-eu"
                       href="?page=fastcomments&isEU=true">I'm in the EU</a>
                </div>
                <p class="fc-hint">If you're not in the EU, continue to Step 2.</p>
            <?php } ?>
        </div>

        <div class="fc-step">
            <h2><span class="fc-step-number">2</span> Connect and Sync</h2>

            <?php if (!get_option('fastcomments_tenant_id')) { ?>
                <ul class="fc-checklist">
                    <li><span class="fc-check"></span> Connect WordPress with FastComments</li>
                    <li><span class="fc-check"></span> Sync Your Comments</li>
                </ul>
                <p><strong>Do you have a FastComments account?</strong></p>
                <div class="fc-actions">
                    <a class="button-primary button-has-account"
                       href="<?php echo FastCommentsPublic::getSite() ?>/auth/my-account/integrations/v1/confirm?token=<?php echo get_option("fastcomments_token") ?>&hasAccount=true"
                       target="_blank">Yes, I have an account</a>
                    <a class="button-primary fc-btn-secondary button-no-account"
                       href="<?php echo FastCommentsPublic::getSite() ?>/auth/my-account/integrations/v1/confirm?token=<?php echo get_option("fastcomments_token") ?>&hasAccount=false"
                       target="_blank">No, create one</a>
                </div>
            <?php } else if (!get_option('fastcomments_setup')) { ?>
                <ul class="fc-checklist">
                    <li class="done"><span class="fc-check">&#10003;</span> Connect WordPress with FastComments</li>
                    <li><span class="fc-check"></span> Sync Your Comments</li>
                </ul>
                <div class="fc-actions">
                    <a class="button-primary"
                       href="<?php echo FastCommentsPublic::getSite() ?>/auth/my-account/integrations/v1/confirm?token=<?php echo get_option("fastcomments_token") ?>&hasAccount=true"
                       target="_blank">Re-Run Setup</a>
                </div>
            <?php } else { ?>
                <ul class="fc-checklist">
                    <li class="done"><span class="fc-check">&#10003;</span> Connect WordPress with FastComments</li>
                    <li class="done"><span class="fc-check">&#10003;</span> Sync Your Comments</li>
                </ul>
                <div class="fc-actions">
                    <a class="button-primary"
                       href="<?php echo FastCommentsPublic::getSite() ?>/auth/my-account/integrations/v1/confirm?token=<?php echo get_option("fastcomments_token") ?>&hasAccount=true"
                       target="_blank">Re-Run Setup</a>
                </div>
            <?php } ?>
        </div>

        <noscript>
            <div class="fc-step">
                <h3>Please enable JavaScript and reload the page to complete setup.</h3>
                <p>JavaScript is not required to use FastComments to comment, but it is required for the initial setup.</p>
            </div>
        </noscript>
    </div>

    <?php
        global $FASTCOMMENTS_VERSION;
        wp_enqueue_script( 'fastcomments_admin_setup_view', plugin_dir_url( __FILE__ ) . 'fastcomments-admin-setup-view.js', array(), $FASTCOMMENTS_VERSION);
    ?>
    <?php wp_localize_script('fastcomments_admin_setup_view', 'FC_DATA', array( 'siteUrl' => get_site_url(), 'nonce' => wp_create_nonce('wp_rest') )); ?>
</div>
