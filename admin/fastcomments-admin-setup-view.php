<div id="fastcomments-admin">
    <h1>Welcome to FastComments.</h1>
    <h3>Let's get you setup.</h3>
    <p>We'll go through a couple steps before FastComments is activated.</p>

    <?php if(!get_option('fastcomments_tenant_id')) { ?>
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
    <?php } else if(!get_option('fastcomments_outbound_sync_done')) { ?>
        <ol>
            <li>✔️ Connect WordPress with FastComments</li>
            <li><input type="checkbox" readonly="readonly" disabled> Sync Your Comments</li>
        </ol>
    <?php } else { ?>
        <ol>
            <li>✔️ Connect WordPress with FastComments</li>
            <li>✔ Sync Your Comments</li>
        </ol>
    <?php } ?>
</div>
