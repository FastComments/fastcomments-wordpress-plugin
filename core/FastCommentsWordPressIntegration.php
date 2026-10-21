<?php

require_once __DIR__ . '/../public/fastcomments-public.php';
require __DIR__ . '/FastCommentsIntegrationCore.php';

class FastCommentsWordPressIntegration extends FastCommentsIntegrationCore {

    public function __construct() {
        $host = FastCommentsPublic::getSite();
        parent::__construct('wordpress', $host);
    }

    private function ensure_plugin_dependencies() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        $id_map_table_name = $wpdb->prefix . "fastcomments_comment_ids";

        $create_id_map_table_sql = "CREATE TABLE $id_map_table_name (
          id varchar(100) NOT NULL,
          wp_id BIGINT(20) NOT NULL,
          PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta($create_id_map_table_sql);

        $event_ids_table_name = $wpdb->prefix . "fastcomments_event_ids"; // TODO AUTO REMOVE OLD EVENT MARKERS

        $create_event_ids_table_sql = "CREATE TABLE $event_ids_table_name (
          id varchar(100) NOT NULL,
          PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta($create_event_ids_table_sql);

        update_option('fc_fastcomments_comment_ids_version', '2.0');

        global $FASTCOMMENTS_VERSION;
        $this->setSettingValue('fastcomments_version', $FASTCOMMENTS_VERSION);

        if (!wp_next_scheduled('fastcomments_cron_hook')) {
            $this->scheduleSync(self::getSyncSchedule());
        }

        if (!get_option('fastcomments_log_level')) {
            update_option('fastcomments_log_level', 'warn');
        }
    }

    public function activate() {
        $this->ensure_plugin_dependencies();
    }

    public function update() {
        global $FASTCOMMENTS_VERSION;

        if ((string)$FASTCOMMENTS_VERSION !== (string)$this->getSettingValue('fastcomments_version')) {
            $is_old_version = !get_option('fc_fastcomments_comment_ids_version');
            if ($is_old_version) {
                // force setup, but allow comment widget to load
                delete_option('fastcomments_setup');
                delete_option('fastcomments_token_validated');
            }

            // Only touch the schedule when it no longer matches the configured interval, so upgrades don't push the next sync out.
            if (wp_get_schedule('fastcomments_cron_hook') !== self::getSyncSchedule()) {
                $this->scheduleSync(self::getSyncSchedule());
            }
            $this->ensure_plugin_dependencies();
            $this->setSettingValue('fastcomments_version', $FASTCOMMENTS_VERSION);
        }
    }

    /** Schedule keys (WP built-ins or the ones registered in fastcomments_cron_schedules) selectable in Advanced Settings. */
    public static function getSyncScheduleOptions() {
        return array(
            'daily' => 'Every 24 hours',
            'hourly' => 'Every hour',
            'fastcomments_fifteen_minutes' => 'Every 15 minutes',
            'fastcomments_five_minutes' => 'Every 5 minutes',
        );
    }

    public static function getSyncSchedule() {
        $schedule = get_option('fastcomments_sync_interval');
        if ($schedule && array_key_exists($schedule, self::getSyncScheduleOptions())) {
            return $schedule;
        }
        return 'daily';
    }

    /** Returns true when the sync job is scheduled afterwards. Never leaves the site without a sync job. */
    public function scheduleSync($schedule) {
        $schedules = wp_get_schedules();
        if (!isset($schedules[$schedule])) {
            $this->log('warn', "Sync schedule $schedule is not registered, falling back to daily.");
            $schedule = 'daily';
        }
        $interval = $schedules[$schedule]['interval'];
        wp_clear_scheduled_hook('fastcomments_cron_hook');
        $scheduled = wp_schedule_event(time() + $interval, $schedule, 'fastcomments_cron_hook');
        if ($scheduled !== true) {
            $this->log('error', "Failed to schedule sync with interval $schedule: " . (is_wp_error($scheduled) ? $scheduled->get_error_message() : 'unknown error'));
            return false;
        }
        $this->log('debug', "Scheduled sync with interval $schedule ($interval seconds).");
        return true;
    }

    /** One-off event so a large initial sync keeps going on normal site traffic instead of waiting for the regular sync schedule. */
    protected function scheduleSendCommentsContinuation($delaySeconds, $replacePending) {
        $pending = wp_next_scheduled('fastcomments_send_comments_continue_hook');
        if ($pending) {
            if (!$replacePending) {
                return;
            }
            wp_unschedule_event($pending, 'fastcomments_send_comments_continue_hook');
        }
        wp_schedule_single_event(time() + $delaySeconds, 'fastcomments_send_comments_continue_hook');
    }

    protected function cancelSendCommentsContinuation() {
        wp_clear_scheduled_hook('fastcomments_send_comments_continue_hook');
    }

    public function deactivate() {
        delete_option('fc_fastcomments_comment_ids_version');
        delete_option('fastcomments_token');
        delete_option('fastcomments_tenant_id');
        delete_option('fastcomments_setup');
        delete_option('fastcomments_sso_key');
        delete_option('fastcomments_sso_enabled');
        delete_option('fastcomments_cron');
        delete_option('fastcomments_version');
        delete_option('fastcomments_stream_last_fetch_timestamp');
        delete_option('fastcomments_stream_last_send_timestamp');
        delete_option('fastcomments_stream_last_send_id');
        delete_option('fastcomments_comment_sent_count');
        delete_option('fastcomments_log_level');
        delete_option('fastcomments_pending_id_mappings');
        delete_option('fastcomments_sync_interval');
        delete_option('fastcomments_sso_users_last_sent_id');
        delete_option('fastcomments_sso_users_synced_at');
        delete_option('fastcomments_token_domain_sent');

        wp_clear_scheduled_hook('fastcomments_cron_hook');
        wp_clear_scheduled_hook('fastcomments_send_comments_continue_hook');
        wp_clear_scheduled_hook('fastcomments_cron'); // hook name used by very old versions
    }

    // note - if the user uninstalls and re-installs - duplicate data my occur as we cleanup the tables to prevent duplicates.
    public function uninstall() {
        global $wpdb;

        $was_drop_successful = $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}fastcomments_comment_ids");

        if (!$was_drop_successful) {
            $this->log('warn', "Dropping of {$wpdb->prefix}fastcomments_comment_ids was not successful!");
        }

        $was_drop_successful = $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}fastcomments_event_ids");

        if (!$was_drop_successful) {
            $this->log('warn', "Dropping of {$wpdb->prefix}fastcomments_event_ids was not successful!");
        }

        // TODO cleanup comment meta items where key = fastcomments_id etc (there is an index on key)
    }

    private static $logLevels = array(
        'debug' => 0, // log everything
        'info' => 1, // log info, warnings, and errors
        'warn' => 2, // only log warnings, errors
        'error' => 3, // only log errors
        'disabled' => 10 // don't log
    );

    public function log($level, $message) {
        $lowestLogLevel = $this->getSettingValue('fastcomments_log_level');
        // handle no log level defined for backwards compat.
        if (!$lowestLogLevel || FastCommentsWordPressIntegration::$logLevels[$lowestLogLevel] <= FastCommentsWordPressIntegration::$logLevels[$level]) {
            $prefixes = array('debug' => 'DEBUG', 'info' => 'INFO', 'warn' => 'WARN', 'error' => 'ERROR');
            if (isset($prefixes[$level])) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- the plugin's logger, the admin controls its volume with the Log Level setting.
                error_log($prefixes[$level] . ":::" . $message);
            }
        }
    }

    public function createUUID() {
        return uniqid("", true);
    }

    public function getDomain() {
        return get_home_url();
    }

    public function getSettingValue($settingName, $fromDB = false) {
        if ($fromDB) {
            wp_cache_delete($settingName, 'options');
        }
        return get_option($settingName);
    }

    public function setSettingValue($settingName, $settingValue, $autoload = true) {
        if ($settingValue === null) {
            delete_option($settingName);
            wp_cache_delete($settingName, 'options');
        } else {
            update_option($settingName, $settingValue);
        }
    }

    private function setEventHandled($eventId) {
        global $wpdb;
        $event_ids_table_name = $wpdb->prefix . "fastcomments_event_ids";
        $insert_result = $wpdb->insert(
            $event_ids_table_name,
            array(
                'id' => $eventId,
            )
        );
        if ($insert_result === false) {
            $this->log('error', "Was not able to mark event $eventId as handled.");
        }
    }

    private function isEventHandled($eventId) {
        global $wpdb;
        $id_row = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}fastcomments_event_ids WHERE id = %s", $eventId));
        if ($id_row) {
            return true;
        }
        return false;
    }

    /**
     * Records a FC→WP mapping after inserting a comment during sync.
     * Stores both the mapping table entry and the comment_meta.
     */
    public function recordSyncMapping($fcId, $wpId) {
        $this->addCommentIDMapEntry($fcId, $wpId);
        update_comment_meta($wpId, 'fastcomments_id', $fcId);
    }

    private function addCommentIDMapEntry($fcId, $wpId) {
        $this->log('debug', "addCommentIDMapEntry $fcId -> $wpId");
        global $wpdb;
        $id_map_table_name = $wpdb->prefix . "fastcomments_comment_ids";
        $existing_wp_id = $this->getWPCommentId($fcId);
        if ($existing_wp_id === null) {
            $insert_result = $wpdb->insert(
                $id_map_table_name,
                array(
                    'id' => $fcId,
                    'wp_id' => $wpId,
                )
            );
            if ($insert_result === false) {
                $this->log('error', "Was not able to map $fcId to $wpId");
            }
        } else if ($existing_wp_id === $wpId) {
            $this->log('debug', "Skipped mapping $fcId to $wpId - mapping already exists.");
        } else {
            $this->log('warn', "Skipped mapping $fcId to $wpId - ALREADY MAPPED TO $existing_wp_id!");
        }
    }

    private function removeCommentIDMapEntry($fcId, $wpId) {
        global $wpdb;
        $wpdb->delete($wpdb->prefix . "fastcomments_comment_ids", array('id' => $fcId, 'wp_id' => $wpId), array('%s', '%d'));
    }

    private function getWPCommentId($fcId) {
        global $wpdb;
        $id_row = $wpdb->get_row($wpdb->prepare("SELECT wp_id FROM {$wpdb->prefix}fastcomments_comment_ids WHERE id = %s", $fcId));
        if ($id_row) {
            return $id_row->wp_id;
        }
        return null;
    }

    /**
     * Fallback lookup: find a WP comment via externalId or meta.wpId from the FC comment.
     * Both are WP primary key lookups via get_comment() — O(1), not a table scan.
     */
    private function getWPCommentIdFromFCComment($fc_comment) {
        // Check meta.wpId first (set when WP sends IDs back after "Download FC → WP" sync — most recent)
        if (isset($fc_comment->meta) && isset($fc_comment->meta->wpId) && is_numeric($fc_comment->meta->wpId)) {
            $wp_comment = get_comment((int)$fc_comment->meta->wpId);
            if ($wp_comment) {
                $this->log('debug', "Found WP comment {$fc_comment->meta->wpId} for FC ID {$fc_comment->_id} via meta.wpId");
                return (int)$fc_comment->meta->wpId;
            }
        }
        // Fall back to externalId (set when comments were originally uploaded from WP → FC)
        if (isset($fc_comment->externalId) && is_numeric($fc_comment->externalId)) {
            $wp_comment = get_comment((int)$fc_comment->externalId);
            if ($wp_comment) {
                $this->log('debug', "Found WP comment {$fc_comment->externalId} for FC ID {$fc_comment->_id} via externalId");
                return (int)$fc_comment->externalId;
            }
        }
        return null;
    }

    public function makeHTTPRequest($method, $url, $body) {
        // Use longer timeout for POST requests (comment uploads can take time with large batches)
        $timeout = ($method === 'POST' && $body) ? 60 : 20;
        $rawResult = wp_remote_request($url, array(
            'method' => $method,
            'body' => $body,
            'timeout' => $timeout,
            'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
            'data_format' => $body ? 'body' : 'query'
        ));

        $result = new stdClass();
        if ($rawResult instanceof WP_Error) {
            $this->log('error', "Request for $url errored out.");
            $result->responseStatusCode = 500;
            $result->responseBody = null;
        } else {
            $result->responseStatusCode = $rawResult['response']['code'];
            $result->responseBody = $rawResult['body'];
        }

//        echo "Response URL " . $method . " " . $url . "\n";
//        echo "Response Code " . $result->responseStatusCode . "\n";
//        echo "Response Body " . $result->responseBody . "\n";

        return $result;
    }

    public function getCurrentUser() {
        $wp_user = wp_get_current_user();
        $fc_user = array();
        if ($wp_user) {
            $fc_user['id'] = $wp_user->ID;
            $fc_user['email'] = $wp_user->user_email;
            $fc_user['username'] = $wp_user->display_name;
            $fc_user['avatar'] = get_avatar_url($wp_user->ID, 95);
            $fc_user['optedInNotifications'] = true;
            $fc_user['optedInSubscriptionNotifications'] = true;
        }
        return $fc_user;
    }

    public function getLoginURL() {
        return wp_login_url();
    }

    public function getLogoutURL() {
        return wp_logout_url();
    }

    public function fc_to_wp_comment($fc_comment, $skipWPCheck) {
        /*
            We intentionally don't send these fields, as they don't apply to us so they won't ever change on our side.
                - comment_author_url
                - comment_author_IP
                - comment_agent
                - comment_type
                - user_id
         */

        $post_id = null;
        $user_id = null;

        if (isset($fc_comment->meta)) {
            if (isset($fc_comment->meta->wpPostId)) {
                $post_id = $fc_comment->meta->wpPostId;
            } else if ($skipWPCheck && isset($fc_comment->urlId)) {
                $post_id = (int)$fc_comment->urlId;
            }
            if (isset($fc_comment->meta->wpUserId)) {
                $user_id = $fc_comment->meta->wpUserId;
            }
        } else if ($skipWPCheck && isset($fc_comment->urlId)) {
            $post_id = (int)$fc_comment->urlId;
        }

        if (!$skipWPCheck && !$post_id) {
            return null; // don't try to set post id to a url... this is probably not a comment from the WP integration.
        }

        $wp_comment = array();

        // wordpress timestamp format is Y-m-d H:i:s (mysql date column type)
        $timestamp = strtotime($fc_comment->date);
        $date_formatted_gmt = gmdate('Y-m-d H:i:s', $timestamp);
        $date_formatted = get_date_from_gmt($date_formatted_gmt);

        $this->log('debug', "Dates: got $date_formatted_gmt -> $date_formatted from $fc_comment->date -> $timestamp");

        $wp_id = $this->getWPCommentId($fc_comment->_id);

        // Fallback: use externalId or meta.wpId from the FC comment (O(1) primary key lookups)
        if ($wp_id === null) {
            $wp_id = $this->getWPCommentIdFromFCComment($fc_comment);
            if ($wp_id !== null) {
                // Re-populate the mapping table so future lookups use the fast path
                $this->addCommentIDMapEntry($fc_comment->_id, $wp_id);
            }
        }

        $parent_wp_id = isset($fc_comment->parentId) && $fc_comment->parentId ? $this->getWPCommentId($fc_comment->parentId) : null;
        $wp_parent_id = $parent_wp_id ? $parent_wp_id : 0;

        $wp_comment['comment_ID'] = is_numeric($wp_id) ? $wp_id : null;
        $wp_comment['comment_post_ID'] = $post_id;
        $wp_comment['comment_post_url'] = $fc_comment->url;
        // Isset check to prevent user ids potentially getting lost if integration is custom and doesn't define meta->wpUserId.
        if (isset($user_id)) {
            $wp_comment['comment_user_ID'] = $user_id;
        }
        $wp_comment['comment_author'] = $fc_comment->commenterName;
        $wp_comment['comment_author_email'] = $fc_comment->commenterEmail;
        $wp_comment['comment_date'] = $date_formatted;
        $wp_comment['comment_date_gmt'] = $date_formatted_gmt;
        $wp_comment['comment_content'] = $fc_comment->commentHTML;
        $wp_comment['comment_karma'] = $fc_comment->votes;
        $wp_comment['comment_approved'] = $fc_comment->approved ? 1 : 0;
        $wp_comment['comment_parent'] = $wp_parent_id;

        return $wp_comment;
    }

    public function wp_to_fc_comment($wp_comment) {
        $fc_comment = array();

        $votes = $wp_comment->comment_karma;

        // Send the ID our backend knows about, to prevent duplicates in some scenarios.
        $meta_fc_id = get_comment_meta($wp_comment->comment_ID, 'fastcomments_id', true);
        if ($meta_fc_id) {
            $fc_comment['_id'] = $meta_fc_id;
        }
        $fc_comment['tenantId'] = $this->getSettingValue('fastcomments_tenant_id');
        $fc_comment['urlId'] = $wp_comment->comment_post_ID;
        $permaLink = get_permalink($wp_comment->comment_post_ID);
        if ($permaLink) {
            $fc_comment['url'] = $permaLink;
        }
        $fc_comment['pageTitle'] = get_the_title($wp_comment->comment_post_ID);
        $fc_comment['userId'] = null;
        $fc_comment['commenterName'] = $wp_comment->comment_author;
        $fc_comment['commenterEmail'] = $wp_comment->comment_author_email;
        $fc_comment['comment'] = $wp_comment->comment_content ? $wp_comment->comment_content : '';
        $fc_comment['externalParentId'] = $wp_comment->comment_parent ? $wp_comment->comment_parent : null; // 0 is the WP default (no parent). we can't do anything with 0.
        $fc_comment['date'] = $wp_comment->comment_date_gmt ? $wp_comment->comment_date_gmt : $wp_comment->comment_date;
        $fc_comment['votes'] = $votes;
        $fc_comment['votesUp'] = $votes > 0 ? $votes : 0;
        $fc_comment['votesDown'] = $votes < 0 ? abs($votes) : 0;
        $fc_comment['verified'] = !!$wp_comment->comment_author_email;
        $fc_comment['verifiedDate'] = null;
        $fc_comment['verificationId'] = null;
        $fc_comment['notificationSentForParent'] = true;
        $fc_comment['notificationSentForParentTenant'] = true;
        $fc_comment['isSpam'] = $wp_comment->comment_approved === 'spam';
        $fc_comment['externalId'] = $wp_comment->comment_ID;
        $fc_comment['avatarSrc'] = null;
        $fc_comment['hasImages'] = false;
        $fc_comment['hasLinks'] = false;
        $fc_comment['approved'] = $wp_comment->comment_approved === '1';

        return $fc_comment;
    }

    public function handleEvents($events) {
        $this->log('debug', "BEGIN handleEvents");
        $mappings = array();
        foreach ($events as $event) {
            try {
                $this->log('debug', "BEGIN handleEvents EVENT $event->_id");

                if ($this->isEventHandled($event->_id)) {
                    $this->log('debug', "END handleEvents EVENT $event->_id SUCCESS - ALREADY HANDLED.");
                    continue;
                }

                /** @type {FastCommentsEventStreamItemData} * */
                $eventData = json_decode($event->data);
                $ourId = null;
                $fcId = null;
                $ourComment = null;
                switch ($eventData->type) {
                    case 'new-comment':
                        $fcId = $eventData->comment->_id;
                        $wp_id = $this->getWPCommentId($fcId);
                        $existingComment = isset($wp_id) ? get_comment($wp_id) : null;
                        $this->log('debug', "Incoming comment $fcId");
                        if ($existingComment) {
                            $this->log('debug', "Incoming comment $fcId will overwrite existing $wp_id");
                            wp_delete_comment($wp_id, true);
                        }
                        if ($wp_id) {
                            $this->removeCommentIDMapEntry($fcId, $wp_id);
                            $this->log('debug', "Removing stale $fcId -> $wp_id mapping.");
                        }

                        $new_wp_comment = $this->fc_to_wp_comment($eventData->comment, false);
                        if ($new_wp_comment) {
                            $comment_id_or_false = wp_insert_comment($new_wp_comment);
                            if ($comment_id_or_false) {
                                $this->recordSyncMapping($fcId, $comment_id_or_false);
                                $mappings[] = array('fcId' => $fcId, 'wpId' => $comment_id_or_false);
                                $this->log('debug', "Saved $fcId (wp id $comment_id_or_false)");
                            } else {
                                $debug_data = $event->data;
                                $this->log('error', "Failed to save comment: $debug_data");
                            }
                        } else {
                            $this->log('debug', "Skipping sync of $fcId - is not from the WP integration.");
                        }
                        break;
                    case 'updated-comment':
                        $fcId = $eventData->comment->_id;
                        $this->log('debug', "Updating comment $fcId");
                        $wp_comment = $this->fc_to_wp_comment($eventData->comment, false);
                        if ($wp_comment) {
                            wp_update_comment($wp_comment);
                            update_comment_meta($wp_comment['comment_ID'], 'fastcomments_id', $eventData->comment->_id, true);
                        } else {
                            $this->log('debug', "Skipping sync of $fcId - is not from the WP integration.");
                        }
                        break;
                    case 'deleted-comment':
                        $fcId = $eventData->comment->_id;
                        $this->log('debug', "Deleting comment $fcId");
                        $wp_id = $this->getWPCommentId($fcId);
                        if (is_numeric($wp_id)) {
                            $was_successful = wp_trash_comment($wp_id);
                            if (!$was_successful) {
                                $this->log('debug', "Attempted to delete $fcId ($wp_id) - but it was not successful!");
                            } else {
                                $this->removeCommentIDMapEntry($fcId, $wp_id);
                            }
                        } else {
                            $this->log('debug', "Attempted to delete $fcId - but it was not found.");
                        }
                        break;
                    case 'new-vote':
                        $fcId = $eventData->vote->commentId;
                        $this->log('debug', "New vote for comment $fcId");
                        $wp_id = $this->getWPCommentId($fcId);
                        $wp_comment = get_comment($wp_id, ARRAY_A);
                        if (!$wp_comment['comment_karma']) {
                            $wp_comment['comment_karma'] = 0;
                        }
                        if ($eventData->vote->direction > 0) {
                            $wp_comment['comment_karma']++;
                        } else {
                            $wp_comment['comment_karma']--;
                        }
                        break;
                    case 'deleted-vote':
                        $fcId = $eventData->vote->commentId;
                        $this->log('debug', "New vote for comment $fcId");
                        $wp_id = $this->getWPCommentId($fcId);
                        $wp_comment = get_comment($wp_id, ARRAY_A);
                        if ($wp_comment['comment_karma']) {
                            if ($eventData->vote->direction > 0) {
                                $wp_comment['comment_karma']--;
                            } else {
                                $wp_comment['comment_karma']++;
                            }
                        }
                        break;
                }
                $this->setEventHandled($event->_id);
                $this->log('debug', "END handleEvents EVENT $event->_id SUCCESS");
            } catch (Exception $e) {
                $this->log('debug', "END handleEvents EVENT $event->_id FAILURE");
                $this->log('error', $e->getMessage());
            }
        }
        $this->sendCommentIdMappings($mappings);
        $this->log('debug', "END handleEvents");
    }

    public function getCommentCount($afterId) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM $wpdb->comments WHERE comment_ID > %d AND comment_approved != 'trash'", $afterId));
    }

    public function getComments($afterId) {
        global $wpdb;
        // Paging by comment_ID after the last sent one keeps the sort stable, so no comment is seen twice.
        $query_result = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->comments WHERE comment_ID > %d AND comment_approved != 'trash' ORDER BY comment_ID ASC LIMIT 100", $afterId));
        $fc_comments = array();
        foreach ($query_result as $wp_comment_row) {
            $wp_comment = get_comment($wp_comment_row);
            if ($wp_comment) {
                array_push($fc_comments, $this->wp_to_fc_comment($wp_comment));
            } else {
                $this->log('warn', "Comment $wp_comment_row->comment_ID was not found from WP after fetching from raw query.");
            }
        }
        return array(
            "status" => "success",
            "comments" => $fc_comments
        );
    }

    /*
     * The user sync reads the users table directly on purpose. WP_User_Query runs other plugins' pre_user_query and
     * users_pre_query hooks: plugins that hide users would silently drop them from the sync, and a users_pre_query
     * short-circuit (e.g. a search index) discards our ID > %d clause, so every page would be the first page and the
     * sync would never finish. These are primary key range scans with a LIMIT, so they stay cheap on large networks,
     * and they are not cached because each page must see the current table.
     * On multisite the users table is shared by the network, so a user counts as a member of this site when they have its capabilities meta.
     */
    // phpcs:disable WordPressVIPMinimum.Variables.RestrictedVariables, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

    public function getUserCount($afterId) {
        global $wpdb;
        if (is_multisite()) {
            return (int)$wpdb->get_var($wpdb->prepare("SELECT count(*) FROM $wpdb->users u INNER JOIN $wpdb->usermeta m ON m.user_id = u.ID AND m.meta_key = %s WHERE u.ID > %d", $wpdb->get_blog_prefix() . 'capabilities', $afterId));
        }
        return (int)$wpdb->get_var($wpdb->prepare("SELECT count(*) FROM $wpdb->users u WHERE u.ID > %d", $afterId));
    }

    private function getUserIdsAfter($afterId, $limit) {
        global $wpdb;
        // Ordering by ID makes the sort stable through pagination.
        if (is_multisite()) {
            return $wpdb->get_col($wpdb->prepare("SELECT u.ID FROM $wpdb->users u INNER JOIN $wpdb->usermeta m ON m.user_id = u.ID AND m.meta_key = %s WHERE u.ID > %d ORDER BY u.ID ASC LIMIT %d", $wpdb->get_blog_prefix() . 'capabilities', $afterId, $limit));
        }
        return $wpdb->get_col($wpdb->prepare("SELECT u.ID FROM $wpdb->users u WHERE u.ID > %d ORDER BY u.ID ASC LIMIT %d", $afterId, $limit));
    }

    // phpcs:enable WordPressVIPMinimum.Variables.RestrictedVariables, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

    public function getUsers($afterId, $limit) {
        $user_ids = $this->getUserIdsAfter($afterId, $limit);
        $fc_users = array();
        cache_users($user_ids); // load the page in one query instead of one per user
        foreach ($user_ids as $user_id) {
            $wp_user = get_userdata($user_id);
            if ($wp_user) {
                array_push($fc_users, $this->wp_to_fc_user($wp_user));
            } else {
                $this->log('warn', "User $user_id was not found from WP after fetching from raw query.");
            }
        }
        $id_count = count($user_ids);
        // Paging goes by the ids, so a user that could not be loaded never ends the sync early.
        return array(
            "users" => $fc_users,
            "lastId" => $id_count > 0 ? (int)$user_ids[$id_count - 1] : null,
            "hasMore" => $id_count === (int)$limit
        );
    }

    public function wp_to_fc_user($wp_user) {
        $fc_user = FastCommentsPublic::getSSOUserData($wp_user);
        if (!isset($fc_user['username'])) {
            $fc_user['username'] = $wp_user->user_login; // FastComments requires a name
        }
        $fc_user['locale'] = function_exists('get_user_locale') ? get_user_locale($wp_user) : get_locale(); // get_user_locale is WP 4.7+
        return $fc_user;
    }

    /**
     * Sends a WordPress user to FastComments as soon as they change, so their FastComments account stays current without a full user sync.
     * @return bool Whether the user was sent and accepted.
     */
    public function syncUser($user_id) {
        // One save fires several user hooks (set_user_role, then profile_update), so the same payload is only sent once per request.
        static $sent = array();
        if (!$this->getSettingValue('fastcomments_sso_enabled')) {
            return false;
        }
        $token = $this->getSettingValue('fastcomments_token');
        if (!$token) {
            return false;
        }
        if (is_multisite() && !is_user_member_of_blog($user_id)) {
            return false;
        }
        $wp_user = get_userdata($user_id);
        if (!$wp_user) {
            return false;
        }
        $fc_user = $this->wp_to_fc_user($wp_user);
        $key = md5(wp_json_encode($fc_user));
        if (isset($sent[$key])) {
            return true;
        }
        $sent[$key] = true;
        $this->log('debug', "Syncing changed user $user_id to FastComments.");
        return $this->sendSSOUser($token, $fc_user);
    }
}
