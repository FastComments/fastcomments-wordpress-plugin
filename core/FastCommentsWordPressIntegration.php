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
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        switch ($method) {
            case "POST":
                if ($body) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($body)
                    ));
                }
                break;
            default:
                if ($body) {
                    $url = sprintf("%s?%s", $url, http_build_query($body));
                }
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($curl, CURLOPT_VERBOSE, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        $rawResult = curl_exec($curl);

        $result = new stdClass();
        $result->responseStatusCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $result->responseBody = $rawResult;

//        echo "Response URL " . $method . " " . $url . "\n";
//        echo "Response Code " . $result->responseStatusCode . "\n";
//        echo "Response Body " . $result->responseBody . "\n";

        curl_close($curl);

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
        $wp_comment = array();
        /*
            We intentionally don't send these fields, as they don't apply to us so they won't ever change on our side.
                - comment_author_url
                - comment_author_IP
                - comment_agent
                - comment_type
                - user_id
         */

        // wordpress timestamp format is Y-m-d H:i:s (mysql date column type)
        $timestamp = strtotime($fc_comment->date);
        $date_formatted = date('Y-m-d H:i:s', $timestamp);


        $wp_id = $this->getWPCommentId($fc_comment->_id);
        $wp_parent_id = isset($fc_comment->parentId) && $fc_comment->parentId ? $this->getWPCommentId($fc_comment->parentId) : 0;

        $wp_comment['comment_ID'] = is_numeric($wp_id) ? $wp_id : null;
        $wp_comment['comment_post_ID'] = (int)$fc_comment->urlId;
        $finalpostId = $wp_comment['comment_post_ID'];
        $wp_comment['comment_post_url'] = $fc_comment->url;
        // TODO fix user ids potentially getting lost via 1. Create comment in WP 2. Sync to FC 3. Update Comment Text in FC 4. Sync back to WP.
//        $wp_comment['comment_user_ID'] = 0;
        $wp_comment['comment_author'] = $fc_comment->commenterName;
        $wp_comment['comment_author_email'] = $fc_comment->commenterEmail;
        $wp_comment['comment_date'] = $date_formatted;
        $wp_comment['comment_date_gmt'] = $date_formatted;
        $wp_comment['comment_content'] = $fc_comment->comment;
        $wp_comment['comment_karma'] = $fc_comment->votes;
        $wp_comment['comment_approved'] = $fc_comment->approved ? 1 : 0;
        $wp_comment['comment_parent'] = $wp_parent_id;

        return $wp_comment;
    }

    public function wp_to_fc_comment($wp_comment) {
        $fc_comment = array();

        $votes = $wp_comment->comment_karma;

        $fc_comment['tenantId'] = $this->getSettingValue('fastcomments_tenant_id');
        $fc_comment['urlId'] = $wp_comment->comment_post_ID;
        $fc_comment['url'] = get_permalink($wp_comment->comment_post_ID);
        $fc_comment['pageTitle'] = get_the_title($wp_comment->comment_post_ID);
        $fc_comment['userId'] = null;
        $fc_comment['commenterName'] = $wp_comment->comment_author;
        $fc_comment['commenterEmail'] = $wp_comment->comment_author_email;
        $fc_comment['comment'] = $wp_comment->comment_content ? $wp_comment->comment_content : '';
        $fc_comment['externalParentId'] = $wp_comment->comment_parent;
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
                            $comment_id_or_false = wp_insert_comment($this->fc_to_wp_comment($eventData->comment));
                            if ($comment_id_or_false) {
                                $this->addCommentIDMapEntry($fcId, $comment_id_or_false);
                            } else {
                                $debug_data = $event->data;
                                $this->log('error', "Failed to save comment: $debug_data");
                            }
                        } else {
                            $this->log('debug', "Incoming comment $fcId ignored, already maps to comment $wp_id");
                        }
                        break;
                    case 'updated-comment':
                        $fcId = $eventData->comment->_id;
                        $this->log('debug', "Updating comment $fcId");
                        $wp_comment = $this->fc_to_wp_comment($eventData->comment);
                        wp_update_comment($wp_comment);
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

    public function getCommentCount($startFromDateTime) {
        if (isset($startFromDateTime)) {
            $args = array(
                'date_query' => array(
                    'after' => date('c', $startFromDateTime ? $startFromDateTime / 1000 : 0)
                ),
                'count' => true
            );
            $wp_comments_count = get_comments($args);
            return $wp_comments_count;
        } else {
            $count_result = wp_count_comments();
            return $count_result ? $count_result->total_comments : 0;
        }
    }

    public function getComments($startFromDateTime) {
        $limit = 100;
        $args = array(
            'number' => $limit + 1,
            'date_query' => array(
                'after' => date('c', $startFromDateTime ? $startFromDateTime / 1000 : 0),
                'inclusive' => true
            ),
            'orderby' => 'comment_date',
            'order' => 'ASC'
        );
        $wp_comments = get_comments($args);
        $has_more = count($wp_comments) > $limit;
        $fc_comments = array();
        for ($i = 0; $i < min(count($wp_comments), $limit); $i++) {
            array_push($fc_comments, $this->wp_to_fc_comment($wp_comments[$i]));
        }
        return array(
            "status" => "success",
            "comments" => $fc_comments,
            "hasMore" => $has_more
        );
    }
}
