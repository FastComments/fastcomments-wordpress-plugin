<!-- TODO check if we have a tenant id setup -->
<!-- TODO ask if they have a fastcomments account -->
<!-- TODO Yes/No -> Go to sync?syncId=x -->

<div id="fastcomments-admin">
    <h1>Welcome to FastComments.</h1>
    <h3>Let's get you setup.</h3>
    <p>We'll go through a few steps before FastComments is activated.</p>

    <ol>
        <li>✔️ Connect WordPress with FastComments</li>
        <li><input type="checkbox" readonly="readonly" disabled> Sync Your Comments</li>
        <li><input type="checkbox" readonly="readonly" disabled> Activate FastComments</li>
    </ol>
    <h2>Do you have a FastComments Account?</h2>
    <a class="button-primary" href="https://fastcomments.com/wp-sync/?syncId=<?php echo get_option("fastcomments_connection_token") ?>" target="_blank">Yes</a>
    <a class="button-primary" href="https://fastcomments.com/wp-sync/?syncId=<?php echo get_option("fastcomments_connection_token") ?>" target="_blank">No</a>
</div>
