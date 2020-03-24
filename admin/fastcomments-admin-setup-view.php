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

    <?php wp_enqueue_script( 'fastcomments_admin_setup_view', plugin_dir_url( __FILE__ ) . 'fastcomments-admin-setup-view.js', array(), $FASTCOMMENTS_VERSION ); ?>
</div>
