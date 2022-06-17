<div id="fastcomments-admin">
    <a class="logo" href="<?php echo FastCommentsPublic::getSite() ?>" target="_blank">
        <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>/admin/images/logo-50.png" alt="FastComments Logo" title="FastComments Logo">
        <span class="text">FastComments.com</span>
    </a>
    <div class="tiles">
        <a href="<?php echo FastCommentsPublic::getSite() ?>/auth/my-account/" target="_blank">
            <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>/admin/images/home.png" alt="My FastComments Account" title="My FastComments Account"/>
            <div>My Account</div>
        </a>
        <a href="<?php echo FastCommentsPublic::getSite() ?>/auth/my-account/moderate-comments" target="_blank">
            <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>/admin/images/crown.png" alt="Moderate Comments" title="Moderate Comments"/>
            <div>Moderate Comments</div>
        </a>
        <a href="<?php echo FastCommentsPublic::getSite() ?>/auth/my-account/customize-widget" target="_blank">
            <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>/admin/images/css.png" alt="Customize Comments" title="Customize Comments"/>
            <div>Customize Comments</div>
        </a>
        <a href="<?php echo FastCommentsPublic::getSite() ?>/auth/my-account/manage-data/export" target="_blank">
            <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>/admin/images/download.png" alt="Export Comments" title="Export Comments"/>
            <div>Export Comments</div>
        </a>
        <a href="<?php echo FastCommentsPublic::getSite() ?>/auth/my-account/integrations/v1/setup?token=<?php echo get_option("fastcomments_token") ?>&hasAccount=true" target="_blank">
            <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>/admin/images/sync-status.png" alt="Check Integration Status" title="Check Integration Status"/>
            <div>Check Integration Status</div>
        </a>
        <a href="<?php echo admin_url('admin.php?page=fastcomments&sub_page=manual-sync'); ?>">
            <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>/admin/images/sync.png" alt="Manually Run Sync" title="Manually Run Sync"/>
            <div>Manually Sync</div>
        </a>
        <a href="<?php echo admin_url('admin.php?page=fastcomments&sub_page=support'); ?>">
            <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>/admin/images/support.png" alt="Support" title="Support"/>
            <div>Support</div>
        </a>
        <a href="<?php echo admin_url('admin.php?page=fastcomments&sub_page=sso'); ?>">
            <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>/admin/images/api.png" alt="SSO" title="SSO"/>
            <div>SSO Settings</div>
        </a>
        <a href="<?php echo admin_url('admin.php?page=fastcomments&sub_page=advanced-settings'); ?>">
            <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>/admin/images/settings.png" alt="Advanced Settings" title="Advanced Settings"/>
            <div>Advanced Settings</div>
        </a>
    </div>
</div>
