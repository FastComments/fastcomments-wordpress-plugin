<?php
/*
Plugin Name: FastComments
Plugin URI: https://fastcomments.com
Description: Live Comments, Fast. A comment system that will delight your users and developers.
Version: 2.1
Author: winrid @ FastComments
License: GPL-2.0+
*/

// Safe guard to prevent loading this from outside wordpress.
if (!defined('WPINC')) {
    die;
}

$FASTCOMMENTS_VERSION = 1.0;


require_once plugin_dir_path(__FILE__) . 'admin/fastcomments-admin.php';
require_once plugin_dir_path(__FILE__) . 'public/fastcomments-public.php';
$fastcomments_public = new FastCommentsPublic();
$fastcomments_public->setup_api_listeners(); // TODO able to do this without new()?

// Returns the FastComments embed comments template
function fc_comments_template()
{
    $path = plugin_dir_path(__FILE__) . 'public/fastcomments-widget-view.php';
    if (!file_exists($path)) {
        throw new Exception("Could not find file! $path");
    }
    return $path;
}

if(get_option('fastcomments_setup')) {
    add_filter('comments_template', 'fc_comments_template', 100);
}

function fastcomments_cron() {
    require_once plugin_dir_path(__FILE__) . 'core/FastCommentsWordPressIntegration.php';
    $fastcomments = new FastCommentsWordPressIntegration();
    $fastcomments->tick();
}

function fastcomments_activate() {
    require_once plugin_dir_path(__FILE__) . 'core/FastCommentsWordPressIntegration.php';
    $fastcomments = new FastCommentsWordPressIntegration();
    $fastcomments->activate();
}

function fastcomments_deactivate() {
    require_once plugin_dir_path(__FILE__) . 'core/FastCommentsWordPressIntegration.php';
    $fastcomments = new FastCommentsWordPressIntegration();
    $fastcomments->deactivate();
}

function fastcomments_update() {
    require_once plugin_dir_path(__FILE__) . 'core/FastCommentsWordPressIntegration.php';
    $fastcomments = new FastCommentsWordPressIntegration();
    $fastcomments->update();
}

register_activation_hook(__FILE__, 'fastcomments_activate');
register_deactivation_hook(__FILE__, 'fastcomments_deactivate');
add_action('plugins_loaded', 'fastcomments_update');

