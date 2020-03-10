<script src="https://cdn.fastcomments.com/js/embed.min.js"></script>
<div id="fastcomments-widget"></div>
<script>
    <?php
    global $post;
    $fcConfig = FastCommentsPublic::get_config_for_post($post);
    ?>
    window.FastCommentsUI(document.getElementById("fastcomments-widget"), <?php echo json_encode($fcConfig); ?>);
</script>
