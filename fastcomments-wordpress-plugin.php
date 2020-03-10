<?php
/*
Plugin Name: FastComments
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Comments, Fast.
Version: 1.0
Author: winrid
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
*/

// Safe gaurd to prevent loading this from outside wordpress.
if (!defined('WPINC')) {
    die;
}

$FASTCOMMENTS_VERSION = 1.0;

// TODO DO NOT ENABLE FOR PRODUCTION
define('WP_DEBUG', true);
define('WP_DEBUG_DISPLAY', false);
define('WP_DEBUG_LOG', true);

require_once plugin_dir_path(__FILE__) . 'admin/fastcomments-admin.php';
require_once plugin_dir_path(__FILE__) . 'public/fastcomments-public.php';
new FastCommentsPublic();

// Returns the FastComments embed comments template
function fc_comments_template()
{
    $path = plugin_dir_path(__FILE__) . 'public/fastcomments-widget-view.php';
    if (!file_exists($path)) {
        throw new Exception("Could not find file! $path");
    }
    return $path;
}

add_filter('comments_template', 'fc_comments_template', 100);

