<div id="fastcomments-admin">
    <a class="logo" href="<?php echo FastCommentsPublic::getSite() ?>" target="_blank">
        <img src="<?php echo plugin_dir_url(dirname(__FILE__)); ?>/admin/images/logo-50.png" alt="FastComments Logo"
             title="FastComments Logo">
        <span class="text">FastComments.com</span>
    </a>
    <a class="fc-back" href="<?php echo admin_url('admin.php?page=fastcomments'); ?>">&larr; Dashboard</a>
    <div class="fc-card">
        <h3>Advanced Settings</h3>
        <?php
        $updated = false;
        if (isset($_POST['log-level']) && $_POST['log-level'] !== get_option('fastcomments_log_level')) {
            update_option('fastcomments_log_level', $_POST['log-level']);
            $updated = true;
        }
        if (isset($_POST['widget']) && $_POST['widget'] !== get_option('fastcomments_widget')) {
            update_option('fastcomments_widget', $_POST['widget']);
            $updated = true;
        }
        if ($updated) {
            ?>
            <div class="notice notice-success is-dismissible" id="settings-updated-success">
                <p><strong>Changes Saved! <a
                                href="<?php echo get_admin_url(null, "/admin.php?page=fastcomments&sub_page=advanced-settings", null) ?>">Refresh</a>.</strong>
                </p>
                <button type="button" class="notice-dismiss" id="settings-updated-success-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
            <?php
        }
        ?>
        <form method="post">
            <table class="form-table" role="presentation">
                <tbody>
                <tr>
                    <th scope="row">
                        <label for="widget">Widget</label>
                    </th>
                    <td>
                        <select name="widget" id="widget">
                            <option value="0" <?php echo !get_option('fastcomments_widget') || get_option('fastcomments_widget') === "0" ? 'selected' : '' ?> >
                                Live Commenting
                            </option>
                            <option value="1" <?php echo get_option('fastcomments_widget') === "1" ? 'selected' : '' ?> >
                                Streaming Chat
                            </option>
                        </select>
                        <p class="description">
                            Changes the type of commenting widget used.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="log-level">Log Level</label>
                    </th>
                    <td>
                        <select name="log-level" id="log-level">
                            <option value="debug" <?php echo !get_option('fastcomments_log_level') || get_option('fastcomments_log_level') === 'debug' ? 'selected' : '' ?> >
                                Debug (Verbose)
                            </option>
                            <option value="info" <?php echo get_option('fastcomments_log_level') === 'info' ? 'selected' : '' ?> >
                                Info
                            </option>
                            <option value="warn" <?php echo get_option('fastcomments_log_level') === 'warn' ? 'selected' : '' ?> >
                                Warn
                            </option>
                            <option value="error" <?php echo get_option('fastcomments_log_level') === 'error' ? 'selected' : '' ?> >
                                Error
                            </option>
                            <option value="disabled" <?php echo get_option('fastcomments_log_level') === 'disabled' ? 'selected' : '' ?> >
                                Disabled
                            </option>
                        </select>
                        <p class="description">
                            Changing the log level might be desired to lower the amount of logs FastComments sends to the
                            PHP error log.
                        </p>
                    </td>
                </tr>
                </tbody>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary">Save Changes</button>
            </p>
        </form>
    </div>
</div>
<?php
global $FASTCOMMENTS_VERSION;
wp_enqueue_script('fastcomments_admin_advanced_settings_view', plugin_dir_url(__FILE__) . 'fastcomments-admin-advanced-settings-view.js', array(), $FASTCOMMENTS_VERSION);
?>
