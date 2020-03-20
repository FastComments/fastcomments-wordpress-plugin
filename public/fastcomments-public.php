<?php

class FastCommentsPublic
{

    public function __construct()
    {
        $this->add_api_listeners();
    }

    public static function get_config_for_post($post)
    {
        return array(
            'tenantId' => get_option('fastcomments_tenant_id') ? get_option('fastcomments_tenant_id') : 'demo',
            'urlId' => strval($post->ID),
            'url' => get_permalink($post),
        );
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
        $json_query_params = $request->get_json_params();

        if ($this->is_request_valid($json_query_params)) {
            return new WP_REST_Response(array("status" => "success"), 200);
        } else {
            return new WP_Error(400, 'Token invalid (token).');
        }
    }

    public
    function handle_update_tenant_id_request(WP_REST_Request $request)
    {
        $json_query_params = $request->get_json_params();

        if ($this->is_request_valid($json_query_params)) {
            if (!$json_query_params['tenantId']) {
                return new WP_Error(400, 'Tenant ID missing (tenantId).');
            }
            update_option('fastcomments_tenant_id', $json_query_params['tenantId']);
            return new WP_REST_Response(array("status" => "success"), 200);
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
                "status" => "success",
                "count" => $count_data ? $count_data->all : 0
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
                "paged" => $json_data['page'],
                "number" => $json_data['count']
            ));

            foreach ($comments as $comment) {
                $comment->comment_post_url = get_permalink($comment->comment_post_ID);
            }
            return new WP_REST_Response(array(
                "status" => "success",
                "comments" => $comments
            ), 200);
        } else {
            return new WP_Error(400, 'Token invalid.');
        }
    }

    public
    function handle_comment_save_request(WP_REST_Request $request)
    {
        $json_data = $request->get_json_params();

        if ($this->is_request_valid($json_data)) {
            $comment_update = $json_data['comment'];
            if ($comment_update['comment_ID']) {
                $was_success = wp_update_comment($comment_update);
                if ($was_success) {
                    return new WP_REST_Response(array(
                        "status" => "success",
                        "comment_ID" => $comment_update['comment_ID']
                    ), 200);
                }
                else {
                    return new WP_Error(500, 'Failed to update.');
                }
            } else {
                $comment_id_or_false = wp_insert_comment($comment_update);
                if ($comment_id_or_false) {
                    return new WP_REST_Response(array(
                        "status" => "success",
                        "comment_ID" => $comment_id_or_false
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
        $json_data = $request->get_json_params();

        if ($this->is_request_valid($json_data)) {
            update_option('fastcomments_setup', $json_data['is-setup']);
            return new WP_REST_Response(array("status" => "success"), 200);
        } else {
            return new WP_Error(400, 'Token invalid.');
        }
    }
}