<?php

class FastCommentsPublic
{

    public function __construct()
    {
        $this->add_api_listeners();
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
        }

        $userDataJSONBase64 = base64_encode(json_encode($sso_user));
        $verificationHash = hash_hmac('sha256', $timestamp . $userDataJSONBase64, $ssoKey);

        $result['userDataJSONBase64'] = $userDataJSONBase64;
        $result['verificationHash'] = $verificationHash;
        $result['loginURL'] = wp_login_url();
        $result['logoutURL'] = wp_logout_url();

        return $result;
    }

    private function add_api_listeners()
    {
        add_action('rest_api_init', function () {
            register_rest_route('fastcomments/v1', '/api/verify', array(
                'methods' => 'POST',
                'callback' => array($this, 'handle_verify_request'),
            ));
            register_rest_route('fastcomments/v1', '/api/update-tenant-id', array(
                'methods' => 'POST',
                'callback' => array($this, 'handle_update_tenant_id_request'),
            ));
            register_rest_route('fastcomments/v1', '/api/update-api-secret', array(
                'methods' => 'POST',
                'callback' => array($this, 'handle_update_api_secret_request'),
            ));
            register_rest_route('fastcomments/v1', '/api/count-comments', array(
                'methods' => 'GET',
                'callback' => array($this, 'handle_comments_count_request'),
            ));
            register_rest_route('fastcomments/v1', '/api/comments', array(
                'methods' => 'GET',
                'callback' => array($this, 'handle_comments_request'),
            ));
            register_rest_route('fastcomments/v1', '/api/comment', array(
                'methods' => 'POST',
                'callback' => array($this, 'handle_comment_save_request'),
            ));
            register_rest_route('fastcomments/v1', '/api/set-setup', array(
                'methods' => 'POST',
                'callback' => array($this, 'handle_set_setup_request'),
            ));
            register_rest_route('fastcomments/v1', '/api/set-sso-enabled', array(
                'methods' => 'POST',
                'callback' => array($this, 'handle_set_sso_enabled_request'),
            ));
            register_rest_route('fastcomments/v1', '/api/get-config-status', array(
                'methods' => 'GET',
                'callback' => array($this, 'handle_get_config_status_request'),
            ));
        });
    }

    private function is_request_valid(array $json_query_params)
    {
        $fcToken = get_option('fastcomments_connection_token', null);
        return $fcToken && $fcToken === $json_query_params['token'];
    }

    public
    function handle_verify_request(WP_REST_Request $request)
    {
        $json_query_params = $this->get_post_body_params($request);

        if ($this->is_request_valid($json_query_params)) {
            return new WP_REST_Response(array('status' => 'success'), 200);
        } else {
            return new WP_Error(400, 'Token invalid (token).');
        }
    }

    public
    function handle_update_tenant_id_request(WP_REST_Request $request)
    {
        $json_query_params = $this->get_post_body_params($request);

        if ($this->is_request_valid($json_query_params)) {
            if (!$json_query_params['tenantId']) {
                return new WP_Error(400, 'Tenant ID missing (tenantId).');
            }
            update_option('fastcomments_tenant_id', $json_query_params['tenantId']);
            return new WP_REST_Response(array('status' => 'success'), 200);
        } else {
            return new WP_Error(400, 'Token invalid.');
        }
    }

    public
    function handle_update_api_secret_request(WP_REST_Request $request)
    {
        $json_query_params = $this->get_post_body_params($request);

        if ($this->is_request_valid($json_query_params)) {
            if (!$json_query_params['secret']) {
                return new WP_Error(400, 'API key missing (secret).');
            }
            update_option('fastcomments_sso_key', $json_query_params['secret']);
            return new WP_REST_Response(array('status' => 'success'), 200);
        } else {
            return new WP_Error(400, 'Token invalid.');
        }
    }

    public
    function handle_comments_count_request(WP_REST_Request $request)
    {
        $json_data = $request->get_query_params();

        if ($this->is_request_valid($json_data)) {
            $count_data = wp_count_comments();
            return new WP_REST_Response(array(
                'status' => 'success',
                'count' => $count_data ? $count_data->total_comments : 0
            ), 200);
        } else {
            return new WP_Error(400, 'Token invalid.');
        }
    }

    public
    function handle_comments_request(WP_REST_Request $request)
    {
        $json_data = $request->get_query_params();

        if ($this->is_request_valid($json_data)) {
            $comments = get_comments(array(
                'offset' => $json_data['count'] * $json_data['page'],
                'orderby' => $json_data['orderby'],
                'number' => $json_data['count']
            ));

            foreach ($comments as $comment) {
                $comment->comment_post_url = get_permalink($comment->comment_post_ID);
                $comment->comment_post_title = get_the_title($comment->comment_post_ID);
            }
            return new WP_REST_Response(array(
                'status' => 'success',
                'comments' => $comments
            ), 200);
        } else {
            return new WP_Error(400, 'Token invalid.');
        }
    }

    public
    function handle_comment_save_request(WP_REST_Request $request)
    {
        $json_data = $this->get_post_body_params($request);

        if ($this->is_request_valid($json_data)) {
            $comment_update = $json_data['comment'];
            if ($comment_update['comment_ID']) {
                $was_updated = wp_update_comment($comment_update);
                return new WP_REST_Response(array(
                    'status' => 'success',
                    'comment_ID' => $comment_update['comment_ID'],
                    'was_updated' => !!$was_updated
                ), 200);
            } else {
                $comment_id_or_false = wp_insert_comment($comment_update);
                if ($comment_id_or_false) {
                    return new WP_REST_Response(array(
                        'status' => 'success',
                        'comment_ID' => $comment_id_or_false
                    ), 200);
                } else {
                    return new WP_Error(500, 'Failed to save.');
                }
            }
        } else {
            return new WP_Error(400, 'Token invalid.');
        }
    }

    public
    function handle_set_setup_request(WP_REST_Request $request)
    {
        $json_data = $this->get_post_body_params($request);

        if ($this->is_request_valid($json_data)) {
            update_option('fastcomments_setup', $json_data['is-setup']);
            return new WP_REST_Response(array('status' => 'success'), 200);
        } else {
            return new WP_Error(400, 'Token invalid.');
        }
    }

    public
    function handle_set_sso_enabled_request(WP_REST_Request $request)
    {
        $json_data = $this->get_post_body_params($request);

        if ($this->is_request_valid($json_data)) {
            update_option('fastcomments_sso_enabled', $json_data['is-enabled'] === true);
            return new WP_REST_Response(array('status' => 'success'), 200);
        } else {
            return new WP_Error(400, 'Token invalid.');
        }
    }

    public
    function handle_get_config_status_request(WP_REST_Request $request)
    {
        return new WP_REST_Response(array('status' => 'success', 'config' => array(
            'fastcomments_tenant_id' => get_option('fastcomments_tenant_id') ? 'setup' : 'not-set',
            'fastcomments_connection_token' => get_option('fastcomments_connection_token') ? 'setup' : 'not-set',
            'fastcomments_sso_key' => get_option('fastcomments_sso_key') ? 'setup' : 'not-set',
            'fastcomments_setup' => get_option('fastcomments_setup') ? 'setup' : 'not-set',
        )), 200);
    }

    private function get_post_body_params(WP_REST_Request $request) {
        // Requests from UI require us to use get_body_params, but from backend requires us to use get_json_params. Not sure why.
        // Content types seem correct, both use POST, etc...
        $json_params = $request->get_json_params();
        if ($json_params === null) {
            $json_params = $request->get_body_params();
        }
        return $json_params;
    }
}