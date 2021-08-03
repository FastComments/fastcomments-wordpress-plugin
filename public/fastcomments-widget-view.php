<div id="fastcomments-widget"></div>
<?php
    if (!get_option('fastcomments_tenant_id') && user_can_access_admin_page()) {
        ?>
            <div style="text-align: center">
                <div style="margin-bottom: 20px">FastComments is not yet setup!</div>
                <a class="button button-primary" href="<?php echo esc_url(get_admin_url(null, 'admin.php?page=fastcomments')) ?>">Complete FastComments Setup</a>
            </div>
        <?php
    } else {
        global $FASTCOMMENTS_VERSION;
        wp_enqueue_script( 'fastcomments_widget_embed', 'https://cdn.fastcomments.com/js/embed-v2.min.js', array(), $FASTCOMMENTS_VERSION, false );
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
                    if (window.FastCommentsUI) {
                        window.FastCommentsUI(document.getElementById('fastcomments-widget'), $jsonFcConfig);
                        return;
                    }
                    setTimeout(attemptToLoad, attempts > 50 ? 500 : 50);
                }
                attemptToLoad();
            })();
        ";
        wp_add_inline_script('fastcomments_widget_embed', $script);
    }
?>
