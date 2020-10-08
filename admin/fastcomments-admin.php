<?php

// Ensure a token exists so that the backend can connect to this wordpress instance.
$existing_token = get_option('fastcomments_connection_token', null);
if (empty($existing_token)) {
    update_option('fastcomments_connection_token', bin2hex(random_bytes(24)));
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
        'href' => 'https://fastcomments.com/auth/my-account/moderate-comments',
    );

    $fastcomments_analytics_node_args = array(
        'parent' => 'fastcomments',
        'id' => 'fastcomments_analytics',
        'title' => 'Analytics',
        'href' => 'https://fastcomments.com/auth/my-account/analytics',
    );

    $fastcomments_customize_node_args = array(
        'parent' => 'fastcomments',
        'id' => 'fastcomments_customize',
        'title' => 'Customize',
        'href' => 'https://fastcomments.com/auth/my-account/customize-widget',
    );

    $fastcomments_my_account_node_args = array(
        'parent' => 'fastcomments',
        'id' => 'fastcomments_my_account',
        'title' => 'My Account',
        'href' => 'https://fastcomments.com/auth/my-account',
    );

    $fastcomments_my_account_node_args = array(
        'parent' => 'fastcomments',
        'id' => 'fastcomments_support',
        'title' => 'Support',
        'href' => admin_url('admin.php?page=fastcomments&sub_page=support'),
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
    $wp_admin_bar->add_node($fastcomments_customize_node_args);
    $wp_admin_bar->add_node($fastcomments_my_account_node_args);
    $wp_admin_bar->add_node($fastcomments_configure_node_args);
}

function fc_plugin_action_links($links, $file)
{
    if ('fastcomments/fastcomments.php' === $file) {
        $plugin_links = array(
            '<a href="' . esc_url(get_admin_url(null, 'admin.php?page=fastcomments')) . '">' .
            ('' === strtolower(get_option('fastcomments_tenant_id')) ? 'Install' : 'Configure') .
            '</a>',
        );
        return array_merge($links, $plugin_links);
    }
    return $links;
}

function fc_render_admin_index()
{
    if (get_option("fastcomments_setup")) {
        global $wp_version;
        switch ($_GET['sub_page']) {
            case 'support':
                global $diagnostic_info;
                $diagnostic_info = array(
                    'fastcomments' => array(
                        'tenant_id' => get_option('fastcomments_tenant_id'),
                        'setup' => get_option('fastcomments_setup'),
                        'sync_token' => get_option('fastcomments_connection_token'),
                        'sso_secret' => get_option('fastcomments_sso_key')
                    ),
                    'wordpress' => array(
                        'version' => $wp_version,
                        'rest_namespaces' => rest_get_server()->get_namespaces(),
                        'plugins' => get_plugins()
                    ),
                    'php' => array(
                        'version' => phpversion()
                    )
                );
                require_once plugin_dir_path(__FILE__) . 'fastcomments-admin-support-view.php';
                break;
            case 'sso':
                require_once plugin_dir_path(__FILE__) . 'fastcomments-admin-sso-view.php';
                break;
            default:
                require_once plugin_dir_path(__FILE__) . 'fastcomments-admin-view.php';
        }
    } else {
        require_once plugin_dir_path(__FILE__) . 'fastcomments-admin-setup-view.php';
    }
}

function fc_render_admin_support()
{
    require_once plugin_dir_path(__FILE__) . 'fastcomments-admin-support-view.php';
}

wp_enqueue_style("fastcomments-admin", plugin_dir_url(__FILE__) . 'fastcomments-admin.css');
add_filter('plugin_action_links', 'fc_plugin_action_links', 10, 2);
add_action('admin_menu', 'fc_contruct_admin_menu');
add_action('admin_bar_menu', 'fc_construct_admin_bar', 1000);
