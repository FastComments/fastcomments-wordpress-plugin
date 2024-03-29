<div id="fastcomments-widget"></div>
<?php
    if (!get_option('fastcomments_tenant_id') && current_user_can('activate_plugins')) {
        ?>
            <div style="text-align: center">
                <div style="margin-bottom: 20px">FastComments is not yet setup!</div>
                <a class="button button-primary" href="<?php echo esc_url(get_admin_url(null, 'admin.php?page=fastcomments')) ?>">Complete FastComments Setup</a>
            </div>
        <?php
    } else if (!post_password_required($post)) {
        global $FASTCOMMENTS_VERSION;
        $cdn = FastCommentsPublic::getCDN();
        $site = FastCommentsPublic::getSite();
        $widgetType = get_option('fastcomments_widget');
        $constructorName = $widgetType === "1" ? 'FastCommentsLiveChat' : 'FastCommentsUI';
        $scriptName = $widgetType === "1" ? 'embed-live-chat' : 'embed-v2';
        wp_enqueue_script( 'fastcomments_widget_embed', "$cdn/js/$scriptName.min.js", array(), $FASTCOMMENTS_VERSION, false );
        global $post;
        $config = FastCommentsPublic::get_config_for_post($post);
        $jsonFcConfig = json_encode($config);
        $urlId = $config['urlId'];
        // These "fcInitializedById" checks are for plugins that try to load the comments more than once for the same url id.
        // The repeated attempt to load is to handle plugins that make our embed script async.
        $script = "
            (function() {
                if (!window.fcInitializedById) {
                    window.fcInitializedById = {};
                }
                if (window.fcInitializedById['$urlId']) {
                    return;
                }
                window.fcInitializedById['$urlId'] = true;
                var attempts = 0;
                function attemptToLoad() {
                    attempts++;
                    var widgetTarget = document.getElementById('fastcomments-widget'); 
                    if (window.$constructorName && widgetTarget) {
                        window.$constructorName(widgetTarget, $jsonFcConfig);
                        return;
                    }
                    setTimeout(attemptToLoad, attempts > 50 ? 500 : 50);
                }
                attemptToLoad();
            })();
        ";
        wp_add_inline_script('fastcomments_widget_embed', $script);

        $tenantIdEncoded = rawurlencode($config['tenantId']);
        $urlEncoded = rawurlencode($config['url']);
        $fastcomments_url = "$site/ssr/comments?tenantId=$tenantIdEncoded&urlId=$urlId&url=$urlEncoded";
        if ($config['sso']) {
            $sso_query_string = rawurlencode(json_encode($config['sso']));
            $fastcomments_url = $fastcomments_url . "&sso=$sso_query_string";
        }
        ?>
        <noscript>
            <iframe
                    src="<?php echo $fastcomments_url; ?>"
                    horizontalscrolling="no"
                    allowtransparency="true"
                    frameborder="0"
                    title="FastComments"
                    width="100%"
                    height="1500px"
                    style="width: 1px !important; min-width: 100% !important; border: none !important; overflow: hidden !important;"
            ></iframe>
        </noscript>
        <?php
    }
?>
