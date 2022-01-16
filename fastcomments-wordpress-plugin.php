<?php
/*
Plugin Name: FastComments
Plugin URI: https://fastcomments.com
Description: Live Comments, Fast. A comment system that will delight your users and developers.
Version: 3.10.2
Author: winrid @ FastComments
License: GPL-2.0+
*/

// Safe guard to prevent loading this from outside wordpress.
if (!defined('WPINC')) {
    die;
}

$FASTCOMMENTS_VERSION = 3.101;

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
function fc_comment_count_template($text_no_comments, $one, $more, $post_id) {
    return "<span class=\"fast-comments-count\" data-fast-comments-url-id=\"$post_id\">$text_no_comments</span>";
}

// Sets up the FastComments embed comment count script if needed.
function fc_comment_count_scripts() {
    if (is_singular()) {
        return;
    }

    global $FASTCOMMENTS_VERSION;
    wp_enqueue_script('fastcomments_widget_count', 'https://cdn.fastcomments.com/js/widget-comment-count-bulk.min.js', array(), $FASTCOMMENTS_VERSION, false);

    $jsonFcConfig = json_encode(array(
        "tenantId" => get_option('fastcomments_tenant_id')
    ));
    // The repeated attempt to load is to handle plugins that make our embed script async.
    $script = "
        (function() {
            var attempts = 0;
            function attemptToLoad() {
                attempts++;
                if (window.FastCommentsCommentCountBulk) {
                    window.FastCommentsCommentCountBulk($jsonFcConfig);
                    return;
                }
                setTimeout(attemptToLoad, attempts > 50 ? 500 : 50);
            }
            attemptToLoad();
        })();
    ";
    wp_add_inline_script('fastcomments_widget_count_embed', $script);
}

// Comments can load as long as we have a tenant id.
if (get_option('fastcomments_tenant_id')) {
    add_filter('comments_template', 'fc_comments_template', 100);
    add_filter('comments_number', 'fc_comment_count_template', 100);
    add_filter('wp_enqueue_scripts', 'fc_comment_count_scripts', 100);
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

