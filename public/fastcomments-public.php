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
            register_rest_route('fastcomments/v1', '/api/sync-to-wp', array(
                'methods' => 'PUT',
                'callback' => array($this, 'handle_sync_to_wp_request'),
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
        if ($should_set_enabled === true || $should_set_enabled === 'true') {
            $fastcomments->enableSSO();
        } else {
            $fastcomments->disableSSO();
        }

        return new WP_REST_Response(array('status' => 'success'), 200);
    }

    public function handle_sync_to_wp_request(WP_REST_Request $request) {
        $includeCount = $request->get_param('includeCount');
        $skip = $request->get_param('skip');
        require_once plugin_dir_path(__FILE__) . '../core/FastCommentsWordPressIntegration.php';
        $fastcomments = new FastCommentsWordPressIntegration();
        $token = $fastcomments->getSettingValue('fastcomments_token');
        $request_url = "https://fastcomments.com/integrations/v1/comments?token=$token";
        if ($includeCount) {
            $request_url .= '&includeCount=true';
        }
        if ($skip) {
            $request_url .= "&skip=$skip";
        }
        $get_comments_response_raw = $fastcomments->makeHTTPRequest('GET', $request_url, null);
        $get_comments_response = json_decode($get_comments_response_raw->responseBody);
        if ($get_comments_response->status === 'success') {
            $count = count($get_comments_response->comments);
            if ($count > 0) {
                foreach ($get_comments_response->comments as $comment) {
                    $wp_comment = $fastcomments->fc_to_wp_comment($comment, true);
                    if(!wp_update_comment($wp_comment)) {
                        wp_insert_comment($wp_comment);
                    }
                }
                return new WP_REST_Response(array('status' => 'success', 'hasMore' => $get_comments_response->hasMore, 'totalCount' => $includeCount ? $get_comments_response->totalCount : null, 'count' => $count), 200);
            } else {
                return new WP_REST_Response(array('status' => 'success'), 200);
            }
        } else {
            return new WP_REST_Response(array('status' => 'failed'), 500);
        }
    }

    public static function get_config_for_post($post) {
        $ssoKey = get_option('fastcomments_sso_key');
        $isSSOEnabled = $ssoKey && get_option('fastcomments_sso_enabled');
        $userId = null;
        $wp_user = wp_get_current_user();
        return array(
            'tenantId' => get_option('fastcomments_tenant_id') ? get_option('fastcomments_tenant_id') : 'demo',
            'urlId' => strval($post->ID),
            'url' => get_permalink($post),
            'readonly' => 'open' != $post->comment_status,
            'sso' => $isSSOEnabled ? FastCommentsPublic::getSSOConfig($ssoKey, $wp_user) : null,
            'apiHost' => null, // For local builds, the CI system will replace this with localhost. This mechanism prevents us from having to read files etc at run time for production sites.
            'commentMeta' => array(
                'wpPostId' => $post->ID,
                'wpUserId' => $wp_user ? $wp_user->ID : null
            )
        );
    }

    private static function getSSOConfig($ssoKey, $wp_user) {
        $timestamp = time() * 1000;

        $result = array();
        $result['timestamp'] = $timestamp;

        $is_admin = current_user_can('administrator');
        $is_moderator = current_user_can('moderate_comments');

        $sso_user = array();
        if ($wp_user) {
            $sso_user['id'] = $wp_user->ID;
            $sso_user['email'] = $wp_user->user_email;
            $sso_user['username'] = $wp_user->display_name;
            $sso_user['avatar'] = get_avatar_url($wp_user->ID, 95);
            $sso_user['optedInNotifications'] = true;
            $sso_user['isAdmin'] = $is_admin;
            $sso_user['isModerator'] = $is_moderator;
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