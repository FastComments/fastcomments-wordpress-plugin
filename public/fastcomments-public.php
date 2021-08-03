<?php

class FastCommentsPublic {

    public function setup_api_listeners() {
        add_action('rest_api_init', function () {
            register_rest_route('fastcomments/v1', '/api/get-config-status', array(
                'methods' => 'GET',
                'callback' => array($this, 'handle_get_config_status_request'),
                'permission_callback' => function () {
                    return current_user_can('activate_plugins');
                }
            ));
            register_rest_route('fastcomments/v1', '/api/tick', array(
                'methods' => 'GET',
                'callback' => array($this, 'handle_tick_request'),
                'permission_callback' => function () {
                    return current_user_can('activate_plugins');
                }
            ));
            register_rest_route('fastcomments/v1', '/api/set-sso-enabled', array(
                'methods' => 'PUT',
                'callback' => array($this, 'handle_set_sso_enabled_request'),
                'permission_callback' => function () {
                    return current_user_can('activate_plugins');
                }
            ));
        });
    }

    public function handle_get_config_status_request(WP_REST_Request $request) {
        require_once plugin_dir_path(__FILE__) . '../core/FastCommentsWordPressIntegration.php';
        $fastcomments = new FastCommentsWordPressIntegration();
        return new WP_REST_Response(array('status' => 'success', 'config' => array(
            'fastcomments_tenant_id' => $fastcomments->getSettingValue('fastcomments_tenant_id') ? 'setup' : 'not-set',
            'fastcomments_token' => $fastcomments->getSettingValue('fastcomments_token') ? 'setup' : 'not-set',
            'fastcomments_sso_key' => $fastcomments->getSettingValue('fastcomments_sso_key') ? 'setup' : 'not-set',
            'fastcomments_setup' => $fastcomments->getSettingValue('fastcomments_setup') ? 'setup' : 'not-set',
        )), 200);
    }

    public function handle_tick_request(WP_REST_Request $request) {
        require_once plugin_dir_path(__FILE__) . '../core/FastCommentsWordPressIntegration.php';
        $fastcomments = new FastCommentsWordPressIntegration();
        $fastcomments->tick();
        return new WP_REST_Response(array('status' => 'success'), 200);
    }

    public function handle_set_sso_enabled_request(WP_REST_Request $request) {
        $should_set_enabled = $request->get_param('is-enabled');
        if ($should_set_enabled == null) {
            return new WP_REST_Response(array('status' => 'failure', 'reason' => 'flag is-enabled missing in request body'), 200);
        }
        require_once plugin_dir_path(__FILE__) . '../core/FastCommentsWordPressIntegration.php';
        $fastcomments = new FastCommentsWordPressIntegration();
        if ($should_set_enabled) {
            $fastcomments->enableSSO();
        } else {
            $fastcomments->disableSSO();
        }

        return new WP_REST_Response(array('status' => 'success'), 200);
    }

    public static function get_config_for_post($post) {
        $ssoKey = get_option('fastcomments_sso_key');
        $isSSOEnabled = $ssoKey && get_option('fastcomments_sso_enabled');
        return array(
            'tenantId' => get_option('fastcomments_tenant_id') ? get_option('fastcomments_tenant_id') : 'demo',
            'urlId' => strval($post->ID),
            'url' => get_permalink($post),
            'readonly' => 'open' != $post->comment_status,
            'sso' => $isSSOEnabled ? FastCommentsPublic::getSSOConfig($ssoKey) : null
        );
    }

    private static function getSSOConfig($ssoKey) {
        $timestamp = time() * 1000;

        $result = array();
        $result['timestamp'] = $timestamp;

        $sso_user = array();
        $wp_user = wp_get_current_user();
        if ($wp_user) {
            $sso_user['id'] = $wp_user->ID;
            $sso_user['email'] = $wp_user->user_email;
            $sso_user['username'] = $wp_user->display_name;
            $sso_user['avatar'] = get_avatar_url($wp_user->ID, 95);
            $sso_user['optedInNotifications'] = true;
        }

        $userDataJSONBase64 = base64_encode(json_encode($sso_user));
        $verificationHash = hash_hmac('sha256', $timestamp . $userDataJSONBase64, $ssoKey);

        $result['userDataJSONBase64'] = $userDataJSONBase64;
        $result['verificationHash'] = $verificationHash;
        $result['loginURL'] = wp_login_url();
        $result['logoutURL'] = wp_logout_url();

        return $result;
    }
}