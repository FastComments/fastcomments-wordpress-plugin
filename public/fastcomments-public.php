<?php

class FastCommentsPublic {

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