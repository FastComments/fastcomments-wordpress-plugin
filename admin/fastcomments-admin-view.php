<!-- TODO check if we have a tenant id setup -->
    <!-- IF NO: -->
        <!-- TODO: if disqus found: show message in top right that we've disabled Disqus -->
        <!-- TODO show sync id -->
        <!-- TODO ask if they have a fastcomments account -->
        <!-- TODO Yes/No -> Go to sync?syncId=x -->
    <! -- IF YES: -->
        <!-- TODO show tile options: moderate comments, manage moderators, trigger manual sync, go to old comments UI-->

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
    <a class="button-primary" href="http://localhost:3001/wp-sync/?syncId=<?php echo get_option("fastcomments_connection_token") ?>&hasAccount=true" target="_blank">Yes</a>
    <a class="button-primary" href="http://localhost:3001/wp-sync/?syncId=<?php echo get_option("fastcomments_connection_token") ?>&hasAccount=false" target="_blank">No</a>
</div>
