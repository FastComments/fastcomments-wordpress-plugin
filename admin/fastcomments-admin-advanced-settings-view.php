<div id="fastcomments-admin">
    <a class="logo" href="https://fastcomments.com" target="_blank">
        <img src="<?php echo plugin_dir_url(dirname(__FILE__)); ?>/admin/images/logo-50.png" alt="FastComments Logo"
             title="FastComments Logo">
        <span class="text">FastComments.com</span>
    </a>
    <h3>FastComments Advanced Settings</h3>
    <?php
    $updated = false;
    if (!empty($_POST['log-level']) && $_POST['log-level'] !== get_option('fastcomments_log_level')) {
        update_option('fastcomments_log_level', $_POST['log-level']);
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
                        Changing the log level might be desired to lower the amount of logs FastComments sends to the PHP error log.
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
<?php
global $FASTCOMMENTS_VERSION;
wp_enqueue_script('fastcomments_admin_advanced_settings_view', plugin_dir_url(__FILE__) . 'fastcomments-admin-advanced-settings-view.js', array(), $FASTCOMMENTS_VERSION);
?>

