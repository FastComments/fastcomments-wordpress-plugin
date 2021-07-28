<?php

class FastCommentsPublic {

    public function setup_api_listeners() {
        add_action('rest_api_init', function () {
            register_rest_route('fastcomments/v1', '/api/get-config-status', array(
                'methods' => 'GET',
                'callback' => array($this, 'handle_get_config_status_request'),
            ));
        });
    }

    public function handle_get_config_status_request(WP_REST_Request $request) {
        // TODO auth
        require_once plugin_dir_path(__FILE__) . '../core/FastCommentsWordPressIntegration.php';
        $fastcomments = new FastCommentsWordPressIntegration();
        $hasToken = $fastcomments->getSettingValue('fastcomments_token');
        if (!$hasToken) {
            $fastcomments->log('debug', 'Setup:::Polling for token...');
            $fastcomments->tick();
            $fastcomments->log('debug', 'Setup:::Polled for token.');
        } else {
            $fastcomments->log('debug', 'Setup:::Not polling for token, we have one.');
            $hasTenantId = $fastcomments->getSettingValue('fastcomments_tenant_id');
            if (!$hasTenantId) {
                $fastcomments->log('debug', 'Setup:::Polling for tenant id... (set when user accepts token in admin).');
                $fastcomments->tick();
                $fastcomments->log('debug', 'Setup:::Polled for tenant id.');
            } else {
                $fastcomments->log('debug', 'Setup:::Not polling for tenant id, we have it.');
                $isSetup = $fastcomments->getSettingValue('fastcomments_setup');
                if (!$isSetup) {
                    $fastcomments->log('debug', 'Setup:::Not yet setup - running state machine.');
                    $fastcomments->tick();
                    $fastcomments->log('debug', 'Setup:::Done running state machine.');
                } else {
                    $fastcomments->log('debug', 'Setup:::Already setup - not running state machine.');
                }
            }
        }
        return new WP_REST_Response(array('status' => 'success', 'config' => array(
            'fastcomments_tenant_id' => $fastcomments->getSettingValue('fastcomments_tenant_id') ? 'setup' : 'not-set',
            'fastcomments_token' => $fastcomments->getSettingValue('fastcomments_token') ? 'setup' : 'not-set',
            'fastcomments_sso_key' => $fastcomments->getSettingValue('fastcomments_sso_key') ? 'setup' : 'not-set',
            'fastcomments_setup' => $fastcomments->getSettingValue('fastcomments_setup') ? 'setup' : 'not-set',
        )), 200);
    }

    public static function get_config_for_post($post)
    {
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

    private static function getSSOConfig($ssoKey)
    {
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