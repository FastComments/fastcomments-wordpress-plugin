<?php

require_once plugin_dir_path(__FILE__) . '../public/fastcomments-public.php';
require_once plugin_dir_path(__FILE__) . '../core/FastCommentsWordPressIntegration.php';
$fastcomments = new FastCommentsWordPressIntegration();
$token = $fastcomments->getSettingValue('fastcomments_token');
if (!$token) {
    $fastcomments->integrationStateCreateToken();
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

    $site = FastCommentsPublic::getSite();

    $fastcomments_moderate_node_args = array(
        'parent' => 'fastcomments',
        'id' => 'fastcomments_moderate',
        'title' => 'Moderate',
        'href' => "$site/auth/my-account/moderate-comments",
    );

    $fastcomments_analytics_node_args = array(
        'parent' => 'fastcomments',
        'id' => 'fastcomments_analytics',
        'title' => 'Analytics',
        'href' => "$site/auth/my-account/analytics",
    );

    $fastcomments_customize_node_args = array(
        'parent' => 'fastcomments',
        'id' => 'fastcomments_customize',
        'title' => 'Customize',
        'href' => "$site/auth/my-account/customize-widget",
    );

    $fastcomments_my_account_node_args = array(
        'parent' => 'fastcomments',
        'id' => 'fastcomments_my_account',
        'title' => 'My Account',
        'href' => "$site/auth/my-account",
    );

    $fastcomments_manual_sync_node_args = array(
        'parent' => 'fastcomments',
        'id' => 'manual-sync',
        'title' => 'Manual Sync',
        'href' => admin_url('admin.php?page=fastcomments&sub_page=manual-sync'),
    );

    $fastcomments_support_node_args = array(
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
    $wp_admin_bar->add_node($fastcomments_my_account_node_args);
    $wp_admin_bar->add_node($fastcomments_moderate_node_args);
    $wp_admin_bar->add_node($fastcomments_analytics_node_args);
    $wp_admin_bar->add_node($fastcomments_customize_node_args);
    $wp_admin_bar->add_node($fastcomments_manual_sync_node_args);
    $wp_admin_bar->add_node($fastcomments_support_node_args);
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
    wp_enqueue_style("fc-google-fonts", "https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@500;600&display=swap", array(), null);
    wp_enqueue_style("fastcomments-admin", plugin_dir_url(__FILE__) . 'fastcomments-admin.css', array('fc-google-fonts'));
    if (get_option("fastcomments_setup")) {
        global $wp_version;
        $subPage = isset($_GET['sub_page']) ? $_GET['sub_page'] : 'n/a';
        switch ($subPage) {
            case 'manual-sync':
                require_once plugin_dir_path(__FILE__) . 'fastcomments-admin-manual-sync-view.php';
                break;
            case 'support':
                global $diagnostic_info;
                $diagnostic_info = array(
                    'fastcomments' => array(
                        'tenant_id' => get_option('fastcomments_tenant_id'),
                        'setup' => get_option('fastcomments_setup'),
                        'sync_token' => get_option('fastcomments_token'),
                        'sso_secret' => get_option('fastcomments_sso_key'),
                        'site' => FastCommentsPublic::getSite(),
                        'cdn' => FastCommentsPublic::getCDN()
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
            case 'advanced-settings':
                require_once plugin_dir_path(__FILE__) . 'fastcomments-admin-advanced-settings-view.php';
                break;
            default:
                require_once plugin_dir_path(__FILE__) . 'fastcomments-admin-view.php';
        }
    } else {
        if (isset($_GET['isEU'])) {
            if ($_GET['isEU'] === 'true') {
                update_option('fastcomments_site', 'https://eu.fastcomments.com');
                update_option('fastcomments_cdn', 'https://cdn-eu.fastcomments.com');
            } else {
                update_option('fastcomments_site', 'https://fastcomments.com');
                update_option('fastcomments_cdn', 'https://cdn.fastcomments.com');
            }
        }
        require_once plugin_dir_path(__FILE__) . 'fastcomments-admin-setup-view.php';
    }
}

function fc_render_admin_support()
{
    wp_enqueue_style("fastcomments-admin", plugin_dir_url(__FILE__) . 'fastcomments-admin.css');
    require_once plugin_dir_path(__FILE__) . 'fastcomments-admin-support-view.php';
}

function fc_admin_setup_notice()
{
    if (!current_user_can('moderate_comments')) {
        return;
    }
    if (get_option('fastcomments_setup')) {
        return;
    }
    $screen = get_current_screen();
    if ($screen && $screen->id === 'toplevel_page_fastcomments') {
        return;
    }
    $setup_url = admin_url('admin.php?page=fastcomments');
    ?>
    <div class="notice notice-warning" style="border-left-color: #5356ec;">
        <p>
            <strong>FastComments</strong> needs to be set up to replace the default WordPress commenting system.
            <a href="<?php echo esc_url($setup_url); ?>">Complete setup &rarr;</a>
        </p>
    </div>
    <?php
}

add_filter('plugin_action_links', 'fc_plugin_action_links', 10, 2);
add_action('admin_menu', 'fc_contruct_admin_menu');
add_action('admin_bar_menu', 'fc_construct_admin_bar', 1000);
add_action('admin_notices', 'fc_admin_setup_notice');
