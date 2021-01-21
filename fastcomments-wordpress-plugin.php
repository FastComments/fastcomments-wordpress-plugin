<?php
/*
Plugin Name: FastComments
Plugin URI: https://fastcomments.com
Description: Live Comments, Fast. A comment system that will delight your users and developers.
Version: 1.8
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

if(get_option('fastcomments_setup')) {
    add_filter('comments_template', 'fc_comments_template', 100);
}

