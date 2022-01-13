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
        <a class="button-primary button-has-account"
           href="https://fastcomments.com/auth/my-account/integrations/v1/setup?token=<?php echo get_option("fastcomments_token") ?>&hasAccount=true"
           target="_blank">Yes</a>
        <a class="button-primary button-no-account"
           href="https://fastcomments.com/auth/my-account/integrations/v1/setup?token=<?php echo get_option("fastcomments_token") ?>&hasAccount=false"
           target="_blank">No</a>
    <?php } else if (!get_option('fastcomments_setup')) { ?>
        <ol>
            <li>✔️️ Connect WordPress with FastComments</li>
            <li><input type="checkbox" readonly="readonly" disabled> Sync Your Comments</li>
        </ol>

        <a class="button-primary"
           href="https://fastcomments.com/auth/my-account/integrations/v1/setup?token=<?php echo get_option("fastcomments_token") ?>&hasAccount=true"
           target="_blank">Re-Run Setup</a>
    <?php } else { ?>
        <!-- This is only here for testing, it should never actually happen due to conditional statements in fastcomments-admin.php before including this file. -->
        <ol>
            <li>✔️ Connect WordPress with FastComments</li>
            <li>✔️ Sync Your Comments</li>
        </ol>

        <a class="button-primary"
           href="https://fastcomments.com/auth/my-account/integrations/v1/setup?token=<?php echo get_option("fastcomments_token") ?>&hasAccount=true"
           target="_blank">Re-Run Setup</a>
    <?php } ?>

    <noscript>
        <h3>Please enable JavaScript and reload the page to complete setup.</h3>
        <p>JavaScript is not required to use FastComments to comment, but it is required for the initial setup.</p>
    </noscript>

    <?php
        global $FASTCOMMENTS_VERSION;
        wp_enqueue_script( 'fastcomments_admin_setup_view', plugin_dir_url( __FILE__ ) . 'fastcomments-admin-setup-view.js', array(), $FASTCOMMENTS_VERSION);
    ?>
    <?php wp_localize_script('fastcomments_admin_setup_view', 'FC_DATA', array( 'siteUrl' => get_site_url(), 'nonce' => wp_create_nonce('wp_rest') )); ?>
</div>
