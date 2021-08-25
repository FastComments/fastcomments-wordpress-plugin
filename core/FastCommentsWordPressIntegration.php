<?php

require(__DIR__ . '/FastCommentsIntegrationCore.php');

class FastCommentsWordPressIntegration extends FastCommentsIntegrationCore {

    public function __construct() {
        parent::__construct('wordpress');
    }

    private function ensure_plugin_dependencies() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $id_map_table_name = $wpdb->prefix . "fastcomments_comment_ids";

        $create_id_map_table_sql = "CREATE TABLE $id_map_table_name (
          id varchar(100) NOT NULL,
          wp_id BIGINT(20) NOT NULL,
          PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($create_id_map_table_sql);
        update_option('fc_fastcomments_comment_ids_version', '1.0');

        global $FASTCOMMENTS_VERSION;
        $this->setSettingValue('fastcomments_version', $FASTCOMMENTS_VERSION);

        $timestamp = wp_next_scheduled('fastcomments_cron_hook');
        if (!$timestamp) {
            wp_schedule_event(time() + 86400, 'daily', 'fastcomments_cron_hook');
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

            $timestamp = wp_next_scheduled('fastcomments_cron_hook');
            wp_unschedule_event($timestamp, 'fastcomments_cron_hook');
            $this->ensure_plugin_dependencies();
            $this->setSettingValue('fastcomments_version', $FASTCOMMENTS_VERSION);
        }
    }

    public function deactivate() {
        global $wpdb;

        $id_map_table_name = $wpdb->prefix . "fastcomments_comment_ids";

        $wpdb->query("DROP TABLE IF EXISTS $id_map_table_name");

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
        delete_option('fastcomments_comment_sent_count');

        $timestamp = wp_next_scheduled('fastcomments_cron');
        wp_unschedule_event($timestamp, 'fastcomments_cron');
    }

    public function log($level, $message) {
        switch ($level) {
            case 'debug':
                error_log("DEBUG:::" . $message);
                break;
            case 'info':
                error_log("INFO:::" . $message);
                break;
            case 'error':
                error_log("ERROR:::" . $message);
                break;
        }
    }

    public function createUUID() {
        return uniqid("", true);
    }

    public function getDomain() {
        return get_home_url();
    }

    public function getSettingValue($settingName) {
        return get_option($settingName);
    }

    public function setSettingValue($settingName, $settingValue) {
        update_option($settingName, $settingValue);
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
        } else {
            $this->log('debug', "Skipped mapping $fcId to $wpId - mapping already exists.");
        }
    }

    private function getWPCommentId($fcId) {
        global $wpdb;
        $id_map_table_name = $wpdb->prefix . "fastcomments_comment_ids";
        $this->log('debug', "getWPCommentId $fcId");
        $id_row = $wpdb->get_row("SELECT wp_id FROM $id_map_table_name WHERE id = \"$fcId\"");
        if ($id_row) {
            return $id_row->wp_id;
        }
        return null;
    }

    public function makeHTTPRequest($method, $url, $body) {
        $rawResult = wp_remote_request($url, array(
            'method' => $method,
            'body' => $body,
            'timeout' => 20,
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
        }
        return $fc_user;
    }

    public function getLoginURL() {
        return wp_login_url();
    }

    public function getLogoutURL() {
        return wp_logout_url();
    }

    public function fc_to_wp_comment($fc_comment) {
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
            }
            if (isset($fc_comment->meta->wpUserId)) {
                $user_id = $fc_comment->meta->wpUserId;
            }
        }

        if (!$post_id) {
            return null; // don't try to set post id to a url... this is probably not a comment from the WP integration.
        }

        $wp_comment = array();

        // wordpress timestamp format is Y-m-d H:i:s (mysql date column type)
        $timestamp = strtotime($fc_comment->date);
        $date_formatted_gmt = date('Y-m-d H:i:s', $timestamp);
        $date_formatted = get_date_from_gmt($date_formatted_gmt);

        $this->log('debug', "Dates: got $date_formatted_gmt -> $date_formatted from $fc_comment->date -> $timestamp");

        $wp_id = $this->getWPCommentId($fc_comment->_id);
        $wp_parent_id = isset($fc_comment->parentId) && $fc_comment->parentId ? $this->getWPCommentId($fc_comment->parentId) : 0;

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
        $wp_comment['comment_content'] = $fc_comment->comment;
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
        $fc_comment['date'] = $wp_comment->comment_date;
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
        foreach ($events as $event) {
            try {
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
                        if (!$existingComment) {
                            $this->log('debug', "Incoming comment $fcId");
                            $new_wp_comment = $this->fc_to_wp_comment($eventData->comment);
                            if ($new_wp_comment) {
                                $comment_id_or_false = wp_insert_comment($new_wp_comment);
                                if ($comment_id_or_false) {
                                    $this->addCommentIDMapEntry($fcId, $comment_id_or_false);
                                    add_comment_meta($comment_id_or_false, 'fastcomments_id', $eventData->comment->_id, true);
                                } else {
                                    $debug_data = $event->data;
                                    $this->log('error', "Failed to save comment: $debug_data");
                                }
                            } else {
                                $this->log('debug', "Skipping sync of $fcId - is not from the WP integration.");
                            }
                        } else {
                            $this->log('debug', "Incoming comment $fcId ignored, already maps to comment $wp_id");
                        }
                        break;
                    case 'updated-comment':
                        $fcId = $eventData->comment->_id;
                        $this->log('debug', "Updating comment $fcId");
                        $wp_comment = $this->fc_to_wp_comment($eventData->comment);
                        if ($wp_comment) {
                            wp_update_comment($wp_comment);
                            add_comment_meta($wp_comment->comment_ID, 'fastcomments_id', $eventData->comment->_id, true);
                        } else {
                            $this->log('debug', "Skipping sync of $fcId - is not from the WP integration.");
                        }
                        break;
                    case 'deleted-comment':
                        $this->log('debug', "Deleting comment $fcId");
                        $wp_id = $this->getWPCommentId($eventData->comment->_id);
                        if (is_numeric($wp_id)) {
                            wp_trash_comment($wp_id);
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
            } catch (Exception $e) {
                $this->log('error', $e->getMessage());
            }
        }
        $this->log('debug', "END handleEvents");
    }

    private function getCommentQueryWhere($startFromDateTime, $afterId) {
        $formattedDate = date('c', $startFromDateTime ? $startFromDateTime / 1000 : 0);
        // This query ensures a stable sort for pagination and allows us to paginate using dates
        // while not seeing the same comment twice.
        return "WHERE comment_date >= $formattedDate
          AND comment_ID > $afterId";
    }

    public function getCommentCount($startFromDateTime, $afterId) {
        $where = $this->getCommentQueryWhere($startFromDateTime, $afterId);
        global $wpdb;
        $sql = "SELECT count(*) FROM $wpdb->comments $where";
        return $wpdb->get_var($sql);
    }

    public function getComments($startFromDateTime, $afterId) {
        $where = $this->getCommentQueryWhere($startFromDateTime, $afterId);
        global $wpdb;
        $sql = "SELECT comment_ID FROM $wpdb->comments $where ORDER BY comment_date, comment_ID ASC LIMIT 500";
        $query_result = $wpdb->get_results($sql);
        $fc_comments = array();
        foreach ($query_result as $wp_comment_row) {
            $wp_comment = get_comment($wp_comment_row->comment_ID);
            if ($wp_comment) {
                array_push($fc_comments, $this->wp_to_fc_comment($wp_comment));
            }
        }
        return array(
            "status" => "success",
            "comments" => $fc_comments
        );
    }
}
