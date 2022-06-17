<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! current_user_can( 'install_plugins' ) ) {
	exit;
}

require_once plugin_dir_path(__FILE__) . 'core/FastCommentsWordPressIntegration.php';

delete_option( 'fastcomments_tenant_id' );
delete_option( 'fastcomments_connection_token' );
delete_option( 'fastcomments_sso_key' );
delete_option( 'fastcomments_sso_enabled' );
delete_option( 'fastcomments_setup' );
delete_option( 'fastcomments_log_level' );
delete_option( 'fastcomments_site' );
delete_option( 'fastcomments_cdn' );

$fastcomments = new FastCommentsWordPressIntegration();
$fastcomments->deactivate();
