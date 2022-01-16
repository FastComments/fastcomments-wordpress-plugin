<?php
/*
Plugin Name: FastComments
Plugin URI: https://fastcomments.com
Description: Live Comments, Fast. A comment system that will delight your users and developers.
Version: 3.10.3
Author: winrid @ FastComments
License: GPL-2.0+
*/

// Safe guard to prevent loading this from outside wordpress.
if (!defined('WPINC')) {
    die;
}

$FASTCOMMENTS_VERSION = 3.103;

require_once plugin_dir_path(__FILE__) . 'admin/fastcomments-admin.php';
require_once plugin_dir_path(__FILE__) . 'public/fastcomments-public.php';
$fastcomments_public = new FastCommentsPublic();
$fastcomments_public->setup_api_listeners(); // TODO able to do this without new()?

// Returns the FastComments embed comments template
function fc_comments_template() {
    $path = plugin_dir_path(__FILE__) . 'public/fastcomments-widget-view.php';
    if (!file_exists($path)) {
        throw new Exception("Could not find file! $path");
    }
    return $path;
}

// Returns the FastComments embed comments template
// This method takes more arguments, like post id, but we found it not to be reliable.
function fc_comment_count_template($text_no_comments = "") {
    global $post;
    $post_id = -1;
    if (isset($post) && $post->ID) {
        $post_id = $post->ID;
    }
    // we add opacity here to prevent flash of content when rendering the original text and then loading our script. We have a style override for users without JS.
    return "<span class=\"fast-comments-count\" data-fast-comments-url-id=\"$post_id\" style=\"opacity: 0;\"'>$text_no_comments</span>";
}

// Sets up the FastComments embed comment count script if needed. This is done this way, with wp_footer, to prevent loading an external script.
function fc_add_comment_count_scripts() {
    global $post;

    if (!isset($post) || is_singular()) {
        return;
    }

    global $FASTCOMMENTS_VERSION;
    wp_enqueue_script('fastcomments_widget_count', 'https://cdn.fastcomments.com/js/embed-widget-comment-count-bulk.min.js', array(), $FASTCOMMENTS_VERSION, true);
}

// Sets up the FastComments embed comment count script if needed. This is done this way, with wp_footer, to prevent loading an external script.
function fc_add_comment_count_config() {
    global $post;

    if (!isset($post) || is_singular()) {
        return;
    }

    $jsonFcConfig = json_encode(array(
        "tenantId" => get_option('fastcomments_tenant_id')
    ));
    echo "<script>window.FastCommentsBulkCountConfig = $jsonFcConfig;</script>";
    echo "<noscript><style>.fast-comments-count { opacity: 1 !important; }</style></noscript>";
}

// Comments can load as long as we have a tenant id.
if (get_option('fastcomments_tenant_id')) {
    add_filter('comments_template', 'fc_comments_template', 100);
    add_filter('comments_number', 'fc_comment_count_template', 100);
    add_filter('wp_enqueue_scripts', 'fc_add_comment_count_scripts', 100);
    add_filter('wp_footer', 'fc_add_comment_count_config', 100);
}

function fastcomments_cron() {
    require_once plugin_dir_path(__FILE__) . 'core/FastCommentsWordPressIntegration.php';
    $fastcomments = new FastCommentsWordPressIntegration();
    $fastcomments->log('debug', 'Begin cron tick.');
    $fastcomments->tick();
    $fastcomments->log('debug', 'End cron tick.');
}

add_action('fastcomments_cron_hook', 'fastcomments_cron');

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

