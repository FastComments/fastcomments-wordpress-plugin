<?php

/**
 * Shared helper to render a FastComments standalone widget (Recent Comments,
 * Recent Discussions, or Top Pages) and load its CDN script.
 */
function fc_render_sidebar_widget($args, $instance, $widget_type) {
    $tenant_id = get_option('fastcomments_tenant_id');
    if (!$tenant_id) {
        return;
    }

    $cdn = FastCommentsPublic::getCDN();

    $map = array(
        'recent_comments'    => array('file' => 'widget-recent-comments-v2.min.js',    'init' => 'FastCommentsRecentCommentsV2',    'el' => 'fc-wp-recent-comments',    'default_title' => __('Recent Comments', 'fastcomments')),
        'recent_discussions' => array('file' => 'widget-recent-discussions-v2.min.js', 'init' => 'FastCommentsRecentDiscussionsV2', 'el' => 'fc-wp-recent-discussions', 'default_title' => __('Recent Discussions', 'fastcomments')),
        'top_pages'          => array('file' => 'widget-top-pages-v2.min.js',          'init' => 'FastCommentsTopPagesV2',          'el' => 'fc-wp-top-pages',          'default_title' => __('Top Pages', 'fastcomments')),
    );
    $meta = $map[$widget_type];

    $title = apply_filters('widget_title', !empty($instance['title']) ? $instance['title'] : $meta['default_title']);
    $count = isset($instance['count']) ? (int) $instance['count'] : ($widget_type === 'recent_discussions' ? 20 : 5);
    if ($count < 1) { $count = 1; }
    if ($count > 50) { $count = 50; }

    $config = array('tenantId' => $tenant_id);
    if ($widget_type !== 'top_pages') {
        $config['count'] = $count;
    }

    $element_id = $meta['el'] . '-' . wp_generate_uuid4();
    $config_json = wp_json_encode($config);
    $src = esc_url($cdn . '/js/' . $meta['file']);
    $init = $meta['init'];

    echo $args['before_widget'];
    if (!empty($title)) {
        echo $args['before_title'] . esc_html($title) . $args['after_title'];
    }
    echo '<div id="' . esc_attr($element_id) . '"></div>';
    // Use printf rather than heredoc to avoid whitespace issues
    printf(
        '<script src="%1$s" defer></script>' .
        '<script>(function(){var attempts=0;function init(){var el=document.getElementById(%2$s);if(el&&window.%3$s){window.%3$s(el,%4$s);return;}if(++attempts<200){setTimeout(init,attempts<50?50:500);}}init();})();</script>',
        $src,
        wp_json_encode($element_id),
        $init,
        $config_json
    );
    echo $args['after_widget'];
}

/**
 * Shared helper for rendering the widget admin form.
 */
function fc_render_sidebar_widget_form($widget, $instance, $has_count = TRUE, $default_count = 5) {
    $title = !empty($instance['title']) ? $instance['title'] : '';
    $count = isset($instance['count']) ? (int) $instance['count'] : $default_count;
    ?>
    <p>
        <label for="<?php echo esc_attr($widget->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'fastcomments'); ?></label>
        <input class="widefat" type="text"
               id="<?php echo esc_attr($widget->get_field_id('title')); ?>"
               name="<?php echo esc_attr($widget->get_field_name('title')); ?>"
               value="<?php echo esc_attr($title); ?>" />
    </p>
    <?php if ($has_count): ?>
    <p>
        <label for="<?php echo esc_attr($widget->get_field_id('count')); ?>"><?php esc_html_e('Count (1-50):', 'fastcomments'); ?></label>
        <input class="tiny-text" type="number" min="1" max="50" step="1"
               id="<?php echo esc_attr($widget->get_field_id('count')); ?>"
               name="<?php echo esc_attr($widget->get_field_name('count')); ?>"
               value="<?php echo esc_attr($count); ?>" />
    </p>
    <?php endif;
}

class FastComments_Recent_Comments_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'fastcomments_recent_comments',
            __('FastComments: Recent Comments', 'fastcomments'),
            array('description' => __('Displays the most recent comments posted across your site.', 'fastcomments'))
        );
    }

    public function widget($args, $instance) {
        fc_render_sidebar_widget($args, $instance, 'recent_comments');
    }

    public function form($instance) {
        fc_render_sidebar_widget_form($this, $instance, TRUE, 5);
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = isset($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        $instance['count'] = isset($new_instance['count']) ? max(1, min(50, (int) $new_instance['count'])) : 5;
        return $instance;
    }
}

class FastComments_Recent_Discussions_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'fastcomments_recent_discussions',
            __('FastComments: Recent Discussions', 'fastcomments'),
            array('description' => __('Displays pages with the most recent discussion activity.', 'fastcomments'))
        );
    }

    public function widget($args, $instance) {
        fc_render_sidebar_widget($args, $instance, 'recent_discussions');
    }

    public function form($instance) {
        fc_render_sidebar_widget_form($this, $instance, TRUE, 20);
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = isset($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        $instance['count'] = isset($new_instance['count']) ? max(1, min(50, (int) $new_instance['count'])) : 20;
        return $instance;
    }
}

class FastComments_Top_Pages_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'fastcomments_top_pages',
            __('FastComments: Top Pages', 'fastcomments'),
            array('description' => __('Displays the most-commented pages on your site.', 'fastcomments'))
        );
    }

    public function widget($args, $instance) {
        fc_render_sidebar_widget($args, $instance, 'top_pages');
    }

    public function form($instance) {
        fc_render_sidebar_widget_form($this, $instance, FALSE);
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = isset($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }
}

function fc_register_sidebar_widgets() {
    register_widget('FastComments_Recent_Comments_Widget');
    register_widget('FastComments_Recent_Discussions_Widget');
    register_widget('FastComments_Top_Pages_Widget');
}
add_action('widgets_init', 'fc_register_sidebar_widgets');

/**
 * Shortcodes for embedding the widgets directly in post/page content.
 * Usage: [fastcomments_recent_comments count="5"]
 */
function fc_sidebar_widget_shortcode($atts, $type) {
    $atts = shortcode_atts(array('count' => $type === 'recent_discussions' ? 20 : 5), $atts);
    $instance = array('count' => (int) $atts['count']);
    $args = array(
        'before_widget' => '<div class="fastcomments-sidebar-widget">',
        'after_widget'  => '</div>',
        'before_title'  => '',
        'after_title'   => '',
    );
    ob_start();
    fc_render_sidebar_widget($args, $instance, $type);
    return ob_get_clean();
}
add_shortcode('fastcomments_recent_comments',    function($atts) { return fc_sidebar_widget_shortcode($atts, 'recent_comments'); });
add_shortcode('fastcomments_recent_discussions', function($atts) { return fc_sidebar_widget_shortcode($atts, 'recent_discussions'); });
add_shortcode('fastcomments_top_pages',          function($atts) { return fc_sidebar_widget_shortcode($atts, 'top_pages'); });
