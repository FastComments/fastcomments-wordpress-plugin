<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! current_user_can( 'install_plugins' ) ) {
	exit;
}

require_once plugin_dir_path(__FILE__) . 'core/FastCommentsIntegrationCore.php';

delete_option( 'fastcomments_tenant_id' );
delete_option( 'fastcomments_connection_token' );
delete_option( 'fastcomments_sso_key' );
delete_option( 'fastcomments_sso_enabled' );
delete_option( 'fastcomments_setup' );

$fastcomments = new FastCommentsWordPressIntegration();
$fastcomments->deactivate();
