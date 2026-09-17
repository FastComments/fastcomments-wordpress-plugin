<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! current_user_can( 'install_plugins' ) ) {
	exit;
}

// Our options never depend on the integration class, so delete them first and
// unconditionally. This keeps a partially-installed plugin (e.g. a folder missing
// core/) uninstallable - deleting a plugin must never fatal on a missing file.
delete_option( 'fastcomments_tenant_id' );
delete_option( 'fastcomments_connection_token' );
delete_option( 'fastcomments_sso_key' );
delete_option( 'fastcomments_sso_enabled' );
delete_option( 'fastcomments_setup' );
delete_option( 'fastcomments_log_level' );
delete_option( 'fastcomments_site' );
delete_option( 'fastcomments_cdn' );
delete_option( 'fastcomments_widget' );
delete_option( 'fastcomments_sync_interval' );
delete_option( 'fastcomments_review_eligibility_started' );
delete_option( 'fastcomments_review_snooze_until' );
delete_option( 'fastcomments_review_dismissed' );
delete_option( 'fastcomments_review_action_taken' );

// Only run the class-backed cleanup when the core file is actually present.
$fc_integration_file = plugin_dir_path( __FILE__ ) . 'core/FastCommentsWordPressIntegration.php';
if ( file_exists( $fc_integration_file ) ) {
	require_once $fc_integration_file;
	$fastcomments = new FastCommentsWordPressIntegration();
	$fastcomments->removeSendCommentsLock();
	$fastcomments->deactivate();
}
