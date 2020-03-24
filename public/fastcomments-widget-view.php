<?php wp_enqueue_script( 'fastcomments_widget_embed', 'https://cdn.fastcomments.com/js/embed.min.js', array(), $FASTCOMMENTS_VERSION ); ?>
<div id="fastcomments-widget"></div>
<?php
global $post;
$jsonFcConfig = json_encode(FastCommentsPublic::get_config_for_post($post));
wp_add_inline_script('fastcomments_widget_view_loader', "window.FastCommentsUI(document.getElementById('fastcomments-widget'), $jsonFcConfig);");
