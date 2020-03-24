<div id="fastcomments-admin">
    <a class="logo" href="https://fastcomments.com" target="_blank">
        <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>/assets/logo.png" alt="FastComments Logo" title="FastComments Logo">
        <span class="text">FastComments.com</span>
    </a>
    <div class="tiles">
        <a href="https://fastcomments.com/auth/my-account/" target="_blank">
            <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>/assets/home.png" alt="My FastComments Account" title="My FastComments Account"/>
            <div>My Account</div>
        </a>
        <a href="https://fastcomments.com/auth/my-account/moderate-comments" target="_blank">
            <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>/assets/crown.png" alt="Moderate Comments" title="Moderate Comments"/>
            <div>Moderate Comments</div>
        </a>
        <a href="https://fastcomments.com/auth/my-account/customize-widget" target="_blank">
            <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>/assets/css.png" alt="Customize Comments" title="Customize Comments"/>
            <div>Customize Comments</div>
        </a>
        <a href="https://fastcomments.com/auth/my-account/manage-data/export" target="_blank">
            <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>/assets/download.png" alt="Export Comments" title="Export Comments"/>
            <div>Export Comments</div>
        </a>
        <a href="https://fastcomments.com/wp-sync/?syncId=<?php echo get_option("fastcomments_connection_token") ?>&hasAccount=true" target="_blank">
            <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>/assets/sync.png" alt="Manually Run Sync" title="Manually Run Sync"/>
            <div>Manually Run Sync</div>
        </a>
        <a href="<?php echo admin_url('admin.php?page=fastcomments&sub_page=support'); ?>">
            <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>/assets/support.png" alt="Support" title="Support"/>
            <div>Support</div>
        </a>
    </div>
</div>
