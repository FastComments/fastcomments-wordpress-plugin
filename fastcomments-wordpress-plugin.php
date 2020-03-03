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
$FASTCOMMENTS_VERSION = 1.0;

require_once plugin_dir_path(__FILE__) . 'admin/fastcomments-admin.php';
require_once plugin_dir_path(__FILE__) . 'public/fastcomments-public.php';

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

