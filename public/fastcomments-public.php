<?php

class FastCommentsPublic
{
    public static function getPostFCID($post)
    {
        return $post->ID . ' ' . $post->guid;
    }

    public static function getConfigForPost($post)
    {
        return array(
            'tenantId' => get_option('fc-tenant-id') ? get_option('fc-tenant-id') : 'demo', // TODO set tenantId
            'urlId' => FastCommentsPublic::getPostFCID($post),
            'url' => get_permalink($post),
        );
    }
}