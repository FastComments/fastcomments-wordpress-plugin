<?php wp_enqueue_script( 'fastcomments_widget_embed', 'https://cdn.fastcomments.com/js/embed.min.js', array(), $FASTCOMMENTS_VERSION, false ); ?>
<div id="fastcomments-widget"></div>
<?php
global $post;
$jsonFcConfig = json_encode(FastCommentsPublic::get_config_for_post($post));
$urlId = $jsonFcConfig['urlId'];
$script = "
    (function() {
        // These checks are for plugins that try to load the comments more than once for the same url id.
        if (!window.fcInitializedById) {
            window.fcInitializedById = {};
        }
        if (window.fcInitializedById['$urlId']) {
            return;
        }
        window.fcInitializedById['$urlId'] = true;
        window.FastCommentsUI(document.getElementById('fastcomments-widget'), $jsonFcConfig);
    })();
";
wp_add_inline_script('fastcomments_widget_embed', $script);
?>
