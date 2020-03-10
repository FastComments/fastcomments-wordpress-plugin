<?php

// Ensure a token exists so that the backend can connect to this wordpress instance.
$existing_token = get_option('fastcomments_connection_token', null);
if (empty($existing_token)) {
    update_option('fastcomments_connection_token', bin2hex(random_bytes(24)));
}

function enqueue_styles()
{
    global $FASTCOMMENTS_VERSION;
    wp_enqueue_style(
        'fastcomments-admin', plugin_dir_url(__FILE__) . 'css/fastcomments-admin.css',
        array(),
        $FASTCOMMENTS_VERSION,
        'all'
    );

}

function enqueue_scripts()
{
    if (!isset($_GET['page']) || 'fastcomments' !== $_GET['page']) {
        return;
    }

    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    global $wp_version;
    global $FASTCOMMENTS_VERSION;

    $admin_js_vars = array(
        'rest' => array(
            'base' => esc_url_raw(rest_url('/')),
            'fastcommentsBase' => 'fastcomments/v1/',

            // Nonce is required so that the REST api permissions can recognize a user/check permissions.
            'nonce' => wp_create_nonce('wp_rest'),
        ),
        'adminUrls' => array(
            'fastcomments' => get_admin_url(null, 'admin.php?page=fastcomments'),
            'editComments' => get_admin_url(null, 'edit-comments.php'),
        ),
        'permissions' => array(
            'canManageSettings' => current_user_can('manage_options'),
        ),
        'site' => array(
            'name' => esc_html(get_bloginfo('name')),
            'pluginVersion' => $FASTCOMMENTS_VERSION,
            'allPlugins' => get_plugins(),
            'phpVersion' => phpversion(),
            'wordpressVersion' => $wp_version,
        ),
    );

    // TODO: Match language of the WordPress installation against any other localizations once they've been set up.
    $language_code = 'en';

    $file = $language_code;
    $file .= '.fastcomments-admin.bundle.';
    $file .= $FASTCOMMENTS_VERSION;
    $file .= WP_DEBUG ? '.js' : '.min.js';

    wp_enqueue_script(
        'fastcomments_admin',
        plugin_dir_url(__FILE__) . 'bundles/js/' . $file,
        array(),
        $FASTCOMMENTS_VERSION,
        true
    );
    wp_localize_script('fastcomments_admin', 'DISQUS_WP', $admin_js_vars);
}

function fc_filter_rest_url($rest_url)
{
    $rest_url_parts = parse_url($rest_url);
    $rest_host = $rest_url_parts['host'];
    if (array_key_exists('port', $rest_url_parts)) {
        $rest_host .= ':' . $rest_url_parts['port'];
    }

    $current_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $rest_host;

    if ($rest_host !== $current_host) {
        $rest_url = preg_replace('/' . $rest_host . '/', $current_host, $rest_url, 1);
    }

    return $rest_url;
}

function fc_contruct_admin_menu()
{
    if (!current_user_can('moderate_comments')) {
        return;
    }

    remove_menu_page('edit-comments.php');

    add_menu_page(
        'FastComments',
        'FastComments',
        'moderate_comments',
        'fastcomments',
        'fc_render_admin_index',
        'dashicons-admin-comments',
        24
    );
}

function fc_construct_admin_bar($wp_admin_bar)
{
    if (!current_user_can('moderate_comments')) {
        return;
    }

    // Replace the WordPress comments menu with the FastComments one.
    $wp_admin_bar->remove_node('comments');

    $fastcomments_node_args = array(
        'id' => 'fastcomments',
        'title' => '<span class="ab-icon"></span>FastComments',
        'href' => admin_url('admin.php?page=fastcomments'),
        'meta' => array(
            'class' => 'fastcomments-menu-bar',
        ),
    );

    $fastcomments_moderate_node_args = array(
        'parent' => 'fastcomments',
        'id' => 'fastcomments_moderate',
        'title' => 'Moderate',
        'href' => get_fastcomments_admin_url('moderate'),
    );

    $fastcomments_analytics_node_args = array(
        'parent' => 'fastcomments',
        'id' => 'fastcomments_analytics',
        'title' => 'Analytics',
        'href' => get_fastcomments_admin_url('analytics/comments'),
    );

    $fastcomments_settings_node_args = array(
        'parent' => 'fastcomments',
        'id' => 'fastcomments_settings',
        'title' => 'Settings',
        'href' => get_fastcomments_admin_url('settings/general'),
    );

    $fastcomments_configure_node_args = array(
        'parent' => 'fastcomments',
        'id' => 'fastcomments_plugin_configure',
        'title' => 'Configure Plugin',
        'href' => admin_url('admin.php?page=fastcomments'),
    );

    $wp_admin_bar->add_node($fastcomments_node_args);
    $wp_admin_bar->add_node($fastcomments_moderate_node_args);
    $wp_admin_bar->add_node($fastcomments_analytics_node_args);
    $wp_admin_bar->add_node($fastcomments_settings_node_args);
    $wp_admin_bar->add_node($fastcomments_configure_node_args);
}

function fc_plugin_action_links($links, $file)
{
    if ('fastcomments/fastcomments.php' === $file) {
        $plugin_links = array(
            '<a href="' . esc_url(get_admin_url(null, 'admin.php?page=fastcomments')) . '">' .
            ('' === strtolower(get_option('fastcomments_forum_url')) ? 'Install' : 'Configure') .
            '</a>',
        );
        return array_merge($links, $plugin_links);
    }
    return $links;
}

function fc_render_admin_index()
{
    require_once plugin_dir_path(__FILE__) . 'fastcomments-admin-view.php';
}

function get_fastcomments_admin_url($path = '')
{
    return 'https://' . strtolower(get_option('fastcomments_forum_url')) . '.fastcomments.com/admin/' . (strlen($path) ? $path . '/' : '');
}

wp_enqueue_style("fastcomments-admin", plugin_dir_url(__FILE__) . 'fastcomments-admin.css');
add_filter('rest_url', 'fc_filter_rest_url');
add_filter('plugin_action_links', 'fc_plugin_action_links', 10, 2);
add_action('admin_enqueue_scripts', 'enqueue_styles');
add_action('admin_enqueue_scripts', 'enqueue_scripts');
add_action('admin_menu', 'fc_contruct_admin_menu');
add_action('admin_bar_menu', 'fc_construct_admin_bar', 1000);
