<?php

require(__DIR__ . '/FastCommentsIntegrationCore.php');

class FastCommentsWordPressIntegration extends FastCommentsIntegrationCore {

    public $fcToOurIds;
    public $commentDB;

    public function __construct() {
        parent::__construct('wordpress');

        $this->fcToOurIds = new TestDB('fcToOurIds'); // we'll need a table, or way to map, the FastComments ids to your ids.
        $this->commentDB = new TestDB('comments'); // we'll need a table to store the comments by id.
    }

    private function ensure_plugin_dependencies() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $id_map_table_name = $wpdb->prefix . "fastcomments_comment_ids";

        $create_id_map_table_sql = "CREATE TABLE $id_map_table_name (
          id tinytext NOT NULL,
          wp_id BIGINT(20) NOT NULL,
          PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($create_id_map_table_sql);
        $timestamp = wp_next_scheduled('fastcomments_cron');
        if (!$timestamp) {
            wp_schedule_event(time() + 86400, 'daily', 'fastcomments_cron');
        }
    }

    public function activate() {
        $this->ensure_plugin_dependencies();

        update_option('fc_fastcomments_comment_ids_version', '1.0');
        update_option('fastcomments_token', null);
        update_option('fastcomments_tenant_id', null);
    }

    public function update() {
        $this->ensure_plugin_dependencies();
    }

    public function deactivate() {
        global $wpdb;

        $id_map_table_name = $wpdb->prefix . "fastcomments_comment_ids";

        $drop_id_map_table_sql = "DROP TABLE $id_map_table_name";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($drop_id_map_table_sql);

        delete_option('fc_fastcomments_comment_ids_version');
        delete_option('fastcomments_token');
        delete_option('fastcomments_tenant_id');

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
        return uniqid();
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
        global $wpdb;
        $id_map_table_name = $wpdb->prefix . "fastcomments_comment_ids";
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
    }

    private function getWPCommentId($fcId) {
        global $wpdb;
        $id_map_table_name = $wpdb->prefix . "fastcomments_comment_ids";
        $id_row = $wpdb->get_row(
            $id_map_table_name,
            array(
                'id' => $fcId
            )
        );
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

    private function fc_to_wp_comment($fc_comment) {
        $wp_comment = array();
        return $wp_comment;
    }

    public function handleEvents($events) {
        foreach ($events as $event) {
            try {
                /** @type {FastCommentsEventStreamItemData} * */
                $eventData = json_decode($event->data);
                $ourId = null;
                $fcId = null;
                $ourComment = null;
                switch ($eventData->type) {
                    case 'new-comment':
                        $comment_id_or_false = wp_insert_comment($this->fc_to_wp_comment($eventData->comment));
                        if ($comment_id_or_false) {
                            $this->addCommentIDMapEntry($fcId, $comment_id_or_false);
                        } else {
                            $debug_data = $event->data;
                            $this->log('error', "Failed to save comment: $debug_data");
                        }
                        break;
                    case 'updated-comment':
                        $wp_comment = $this->fc_to_wp_comment($eventData->comment);
                        $wp_id = $this->getWPCommentId($eventData->comment->_id);
                        $wp_comment['comment_ID'] = $wp_id;
                        wp_update_comment($wp_comment);
                        break;
                    case 'deleted-comment':
                        $wp_id = $this->getWPCommentId($eventData->comment->_id);
                        if ($wp_id != null) {
                            wp_trash_comment($wp_id);
                        }
                        break;
                    case 'new-vote':
                        $wp_id = $this->getWPCommentId($eventData->vote->commentId);
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
                        $wp_id = $this->getWPCommentId($eventData->vote->commentId);
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
    }

    public function getCommentCount() {
        $count_result = wp_count_comments();
        return $count_result ? $count_result->total_comments : 0;
    }

    public function getComments($startFromDateTime) {
        // obviously, you would use a proper database with carefully designed indexes, right? :)

        $comments = FastCommentsWordPressIntegration::getCommentsFrom($this->commentDB->getData(), $startFromDateTime);
        $remainingComments = count($comments) > 0 ? FastCommentsWordPressIntegration::getCommentsFrom($this->commentDB->getData(), $comments[count($comments) - 1]['date']) : [];
        return array(
            "status" => "success",
            "comments" => $comments,
            "hasMore" => count($remainingComments) > 0
        );
    }

    public function setupAPIListeners() {
        add_action('rest_api_init', function () {
            register_rest_route('fastcomments/v1', '/api/get-config-status', array(
                'methods' => 'GET',
                'callback' => array($this, 'handle_get_config_status_request'),
            ));
        });
    }

    public function handle_get_config_status_request(WP_REST_Request $request) {
        $hasToken = $this->getSettingValue('fastcomments_token') !== null;
        if (!$hasToken) {
            $this->log('debug', 'Polling for token...');
            $this->tick();
            $this->log('debug', 'Polled for token...');
        } else {
            $hasTenantId = $this->getSettingValue('fastcomments_tenant_id') !== null;
            if (!$hasTenantId) {
                $this->log('debug', 'Polling for tenant id... (set when user accepts token in admin).');
                $this->tick();
                $this->log('debug', 'Polled for tenant id...');
            }
        }
        return new WP_REST_Response(array('status' => 'success', 'config' => array(
            'fastcomments_tenant_id' => $this->getSettingValue('fastcomments_tenant_id') ? 'setup' : 'not-set',
            'fastcomments_token' => $this->getSettingValue('fastcomments_token') ? 'setup' : 'not-set',
            'fastcomments_sso_key' => $this->getSettingValue('fastcomments_sso_key') ? 'setup' : 'not-set',
            'fastcomments_setup' => $this->getSettingValue('fastcomments_setup') ? 'setup' : 'not-set',
        )), 200);
    }
}

function fastcomments_cron() {
    $fastcomments = new FastCommentsWordPressIntegration();
    $fastcomments->tick();
}

function fastcomments_activate() {
    $fastcomments = new FastCommentsWordPressIntegration();
    $fastcomments->activate();
}

function fastcomments_deactivate() {
    $fastcomments = new FastCommentsWordPressIntegration();
    $fastcomments->deactivate();
}

function fastcomments_update() {
    $fastcomments = new FastCommentsWordPressIntegration();
    $fastcomments->update();
}

register_activation_hook(__FILE__, 'fastcomments_activate');
register_deactivation_hook(__FILE__, 'fastcomments_deactivate');
add_action('plugins_loaded', 'fastcomments_update');
