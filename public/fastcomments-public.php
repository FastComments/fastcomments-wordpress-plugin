<?php

class FastCommentsPublic
{

    public function __construct()
    {
        $this->add_api_listeners();
    }

    public static function get_post_url_id($post)
    {
        return $post->ID . ' ' . $post->guid;
    }

    public static function get_config_for_post($post)
    {
        return array(
            'tenantId' => get_option('fc-tenant-id') ? get_option('fc-tenant-id') : 'demo', // TODO set tenantId
            'urlId' => FastCommentsPublic::get_post_url_id($post),
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
            register_rest_route('fastcomments/v1', '/api/comments', array(
                'methods' => 'GET',
                'callback' => array($this, 'handle_comments_request'),
            ));
        });
    }

    public function handle_verify_request(WP_REST_Request $request)
    {
        $json_query_params = $request->get_query_params();

        $fcToken = get_option('fastcomments_connection_token', null);
        if ($fcToken && $fcToken === $json_query_params['token']) {
            // TODO update WP status
            return new WP_REST_Response(json_encode(array("status" => "success")), 200);
        } else {
            return new WP_Error(400, 'Token invalid.');
        }
    }

    public function handle_comments_request(WP_REST_Request $request)
    {
        $json_data = $request->get_json_params();
        $fcToken = get_option('fastcomments_connection_token', null);
        if (!$fcToken || $fcToken !== $json_data['token']) {
            return new WP_Error(400, 'Token invalid.');
        }
        return "";
    }
}