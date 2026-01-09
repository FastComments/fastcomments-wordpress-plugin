<?php

abstract class FastCommentsIntegrationCore {
    public $baseUrl;
    public $integrationType;

    public function __construct($integrationType, $host = 'https://fastcomments.com') {
        if (!$integrationType) {
            throw new RuntimeException('An integration type is required! Ex: new FastCommentsIntegrationCore("wordpress")');
        }
        if (!$host) {
            throw new RuntimeException('An integration host is required! A valid default is available, did you try to set this to a weird value?');
        }
        $this->integrationType = $integrationType;
        $this->baseUrl = "$host/integrations/v1";
    }

    public abstract function activate();

    public abstract function deactivate();

    public abstract function createUUID();

    public abstract function getDomain();

    public abstract function getSettingValue($settingName);

    public abstract function setSettingValue($settingName, $settingValue);

    public abstract function makeHTTPRequest($method, $url, $body);

    public abstract function getCurrentUser();

    public abstract function getLoginURL();

    public abstract function getLogoutURL();

    public abstract function handleEvents($events);

    public abstract function getCommentCount($afterId);

    public abstract function getComments($afterId);

    public function base64Encode($stringValue) {
        return base64_encode($stringValue);
    }

    public function log($level, $message) {
        switch ($level) {
            case 'debug':
                echo "DEBUG:::" . $message . "\n";
                break;
            case 'info':
                echo "INFO:::" . $message . "\n";
                break;
            case 'error':
                echo "ERROR:::" . $message . "\n";
                break;
        }
    }

    public function getConfig($timestamp, $urlId, $url, $isReadonly) {
        $ssoKey = $this->getSettingValue('fastcomments_sso_key');
        $tenantId = $this->getSettingValue('fastcomments_tenant_id');
        $isSSOEnabled = $ssoKey && $this->getSettingValue('fastcomments_sso_enabled');
        $result = new stdClass();
        $result->tenantId = ($tenantId) ? $tenantId : 'demo';
        $result->urlId = $urlId;
        $result->url = $url;
        $result->readonly = $isReadonly;
        $result->sso = ($isSSOEnabled) ? $this->getSSOConfig($timestamp, $ssoKey) : null;
        return $result;
    }

    public function getSSOConfig($timestamp, $ssoKey) {
        $result = new stdClass();
        $result->timestamp = $timestamp;
        $sso_user = new stdClass();
        $user = $this->getCurrentUser();
        if ($user) {
            $sso_user->id = $user->id;
            $sso_user->email = $user->email;
            $sso_user->username = $user->username;
            $sso_user->avatar = $user->avatar;
            $sso_user->optedInNotifications = true;
        }
        $userDataJSONBase64 = $this->base64Encode(json_encode($sso_user));
        $verificationHash = hash_hmac('sha256', $timestamp->userDataJSONBase64, $ssoKey);
        $result->userDataJSONBase64 = $userDataJSONBase64;
        $result->verificationHash = $verificationHash;
        $result->loginURL = $this->getLoginURL();
        $result->logoutURL = $this->getLogoutURL();
        return $result;
    }

    public function enableSSO() {
        $token = $this->getSettingValue("fastcomments_token");
        $apiSecretResponseRaw = $this->makeHTTPRequest('GET', "$this->baseUrl/api-secret?token=$token", null);
        $this->log('debug', 'Secret Token Fetch Response Code:' . $apiSecretResponseRaw->responseStatusCode);
        $apiSecretResponse = json_decode($apiSecretResponseRaw->responseBody);
        if ($apiSecretResponse->status === 'success' && $apiSecretResponse->secret) {
            $this->setSettingValue('fastcomments_sso_key', $apiSecretResponse->secret);
            $this->setSettingValue('fastcomments_sso_enabled', true);
        } else {
            $this->log('error', $apiSecretResponse->reason);
            throw new RuntimeException("API did not return success response when trying to get the key!");
        }
    }

    public function disableSSO() {
        $this->setSettingValue('fastcomments_sso_key', null);
        $this->setSettingValue('fastcomments_sso_enabled', false);
    }

    public function tick() {
        $this->log('debug', "BEGIN Tick");
        $nextStateMachineName = 'integrationStateInitial';
        while ($nextStateMachineName) {
            $this->log('debug', 'Next state machine:' . $nextStateMachineName);
            $nextStateMachineName = call_user_func(array($this, $nextStateMachineName));
        }
        $this->log('debug', "END Tick");
    }

    public function integrationStateInitial() {
        $tenantId = $this->getSettingValue('fastcomments_tenant_id');
        $token = $this->getSettingValue('fastcomments_token');
        $isTokenValidated = $this->getSettingValue('fastcomments_token_validated');
        if ($tenantId && $token && $isTokenValidated) {
            return 'integrationStatePollNext';
        } else {
            if ($token) {
                return 'integrationStateValidateToken';
            } else {
                return 'integrationStateCreateToken';
            }
        }
    }

    public function integrationStateValidateToken() {
        $token = $this->getSettingValue('fastcomments_token');
        if ($token) {
            $domainName = $this->getDomain();
            $rawTokenUpsertResponse = $this->makeHTTPRequest('PUT', "$this->baseUrl/token?token=$token&integrationType=$this->integrationType&domain=$domainName", null);

            if ($rawTokenUpsertResponse->responseStatusCode !== 200) {
                $this->log('warn', "Token validation HTTP request failed with status code: {$rawTokenUpsertResponse->responseStatusCode}");
                return null;
            }

            if (empty($rawTokenUpsertResponse->responseBody)) {
                $this->log('warn', "Token validation received empty response body");
                return null;
            }

            $tokenUpsertResponse = json_decode($rawTokenUpsertResponse->responseBody);

            if ($tokenUpsertResponse === null) {
                $this->log('warn', "Token validation failed to parse JSON response: " . substr($rawTokenUpsertResponse->responseBody, 0, 200));
                return null;
            }

            $status = isset($tokenUpsertResponse->status) ? $tokenUpsertResponse->status : 'unknown';
            $isValidated = isset($tokenUpsertResponse->isTokenValidated) ? $tokenUpsertResponse->isTokenValidated : false;
            $tenantId = isset($tokenUpsertResponse->tenantId) ? $tokenUpsertResponse->tenantId : null;

            $this->log('debug', "Token validation response: status={$status} isTokenValidated=" . var_export($isValidated, true) . " tenantId=" . var_export($tenantId, true));

            if ($status === 'success' && $isValidated === true && !empty($tenantId)) {
                $this->setSettingValue('fastcomments_tenant_id', $tenantId);
                $this->setSettingValue('fastcomments_token_validated', true);
                $this->log('info', "Token validated successfully, tenant_id set to {$tenantId}");
            } else if ($status === 'success' && $isValidated === true) {
                $this->log('warn', "Token validated but tenantId was empty/null - not setting tenant_id");
            } else if ($status === 'success' && $isValidated === false) {
                $this->log('debug', "Token not yet validated by user on FastComments side");
            } else {
                $this->log('warn', "Token validation got unexpected response - status: {$status}, isValidated: " . var_export($isValidated, true));
            }
            return null;
        } else {
            return 'integrationStateCreateToken';
        }
    }

    public function integrationStateCreateToken() {
        $newUUID = $this->createUUID();
        $this->setSettingValue('fastcomments_token', $newUUID);
        return null;
    }

    public function integrationStatePollNext() {
        // One idea to consider, that'd be easier to understand, would be to store the commands locally and a queue and process them.
        // This removes the weird logic where each time a command is finished processing, we advance the fastcomments_stream_last_fetch_timestamp.
        // The reason this logic is weird is the two things are relatively far from each other, potentially being bug prone.
        $token = $this->getSettingValue('fastcomments_token');
        if ($token) {
            $lastFetchDate = $this->getSettingValue('fastcomments_stream_last_fetch_timestamp', true);
            $lastFetchDateToSend = $lastFetchDate ? $lastFetchDate : 0;
            $this->log('debug', "Polling next commands for fromDateTime=[$lastFetchDateToSend].");
            $rawIntegrationStreamResponse = $this->makeHTTPRequest('GET', "$this->baseUrl/commands?token=$token&fromDateTime=$lastFetchDateToSend", null);
            $this->log('debug', 'Stream response status: ' . $rawIntegrationStreamResponse->responseStatusCode);
            if ($rawIntegrationStreamResponse->responseStatusCode === 200) {
                $response = json_decode($rawIntegrationStreamResponse->responseBody);
                if ($response->status === 'success' && $response->commands) {
                    foreach ($response->commands as $command) {
                        $this->log('debug', "Processing command $command->command");
                        switch ($command->command) {
                            case 'FetchEvents':
                                $this->commandFetchEvents($token);
                                break;
                            case 'SendComments':
                                $this->commandSendComments($token);
                                break;
                            case 'SetSyncDone':
                                $this->commandSetSyncDone();
                                break;
                        }
                    }
                }
            }
        } else {
            $this->log('error', "Cannot fetch commands, fastcomments_token not set.");
        }
        return null;
    }

    public function commandFetchEvents($token) {
        $this->log('debug', "BEGIN commandFetchEvents");
        $fromDateTime = $this->getSettingValue('fastcomments_stream_last_fetch_timestamp', true);
        $hasMore = true;
        $startedAt = time();
        while ($hasMore && time() - $startedAt < 30) {
            $fromDateTimeToSend = $fromDateTime ? $fromDateTime : 0;
            $this->log('debug', "Send events command loop... Fetching events fromDateTime=[$fromDateTimeToSend]");
            $rawIntegrationEventsResponse = $this->makeHTTPRequest('GET', "$this->baseUrl/events?token=$token&fromDateTime=$fromDateTimeToSend", null);
            $response = json_decode($rawIntegrationEventsResponse->responseBody);
            if ($response->status === 'success') {
                $count = count($response->events);
                $this->log('info', "Got events count=[$count] hasMore=[$response->hasMore]");
                if ($response->events && count($response->events) > 0) {
                    $this->handleEvents($response->events);
                    $fromDateTime = strtotime($response->events[count($response->events) - 1]->createdAt) * 1000;
                }
                $hasMore = !!$response->hasMore;
                $this->setSettingValue('fastcomments_stream_last_fetch_timestamp', $fromDateTime, false);
            } else {
                $this->log('error', "Failed to get events: {$rawIntegrationEventsResponse}");
                break;
            }
        }
        $this->log('debug', "END commandFetchEvents");
    }

    private function canAckLock($name, $windowSeconds) {
        $settingName = $this->getLockName($name);
        $this->log('debug', "BEGIN canAckLock $settingName with window $windowSeconds");
        $lastTime = $this->getSettingValue($settingName, true);
        if (!$lastTime) {
            $this->log('debug', "END canAckLock $settingName last lock time $lastTime. Got lock=[1]");
            return true;
        }
        $now = time();
        $delta = $now - ((int) ($lastTime));
        $gotLock = $delta >= $windowSeconds;
        $this->log('debug', "END canAckLock $settingName last lock time $lastTime. Delta=[$delta] Got lock=[$gotLock]");
        return $gotLock;
    }

    private function tryAckLock($name, $windowSeconds) {
        $settingName = $this->getLockName($name);
        $this->log('debug', "BEGIN tryAckLock $settingName with window $windowSeconds");
        $secondsRemaining = 5;
        $retryInterval = 1;
        $gotLock = $this->canAckLock($name, $windowSeconds);
        while (!$gotLock && $secondsRemaining > 0) {
            $gotLock = $this->canAckLock($name, $windowSeconds);
            $this->log('debug', "PROGRESS tryAckLock $settingName with window $windowSeconds in loop. Got lock=[$gotLock]");
            if (!$gotLock) {
                $secondsRemaining = $secondsRemaining - $retryInterval;
                sleep($retryInterval);
            }
        }
        if ($gotLock) {
            $now = time();
            $this->log('debug', "PROGRESS tryAckLock acquiring lock $settingName for now=[$now]");
            $this->setSettingValue($settingName, $now, false);
        }
        $this->log('debug', "END tryAckLock $settingName Got lock=[$gotLock]");
        return $gotLock;
    }

    private function resetLock($name, $windowSeconds) {
        $settingName = $this->getLockName($name);
        $attemptedNewValue = time() + $windowSeconds;
        $this->log('debug', "BEGIN resetLock $settingName for window $windowSeconds (setting to=[$attemptedNewValue])");
        $this->setSettingValue($settingName, $attemptedNewValue, false);
        $newValue = $this->getSettingValue($settingName, true);
        $this->log('debug', "END resetLock $settingName for window $windowSeconds. New value=[$newValue]");
    }

    private function getLockName($name) {
        return "lock_$name";
    }

    private function clearLock($name) {
        $settingName = $this->getLockName($name);
        $this->log('debug', "BEGIN clearLock $settingName");
        $this->setSettingValue($settingName, null, false);
        $wasCleared = $this->getSettingValue($settingName, true);
        $this->log('debug', "END clearLock $settingName. New value=[$wasCleared]");
    }

    public function removeSendCommentsLock() {
        $this->clearLock("commandSendComments");
    }

    public function commandSendComments($token) {
        /**
         * Fetch 100 comments a time from the DB.
         * If the server complains the payload is too large, recursively split the chunk by / 10.
         */
        $this->log('debug', 'Starting to send comments');
        // We use try and not "canAckLock" in case the cron runs within a second of sync, don't let cron fail.
        if (!$this->tryAckLock("commandSendComments", 60)) {
            $this->log('debug', 'Can not send right now, waiting for previous attempt to finish.');
            return 'LOCK_WAITING';
        }
        $lastSendDate = $this->getSettingValue('fastcomments_stream_last_send_timestamp', true);
        $lastSentId = $this->getSettingValue('fastcomments_stream_last_send_id', true);
        $commentCount = $this->getCommentCount($lastSentId ? $lastSentId : -1);
        if ($commentCount == 0) {
            $this->log('debug', "No comments to send. Telling server. lastSendDate=[$lastSendDate] lastSentId=[$lastSentId]");
            // TODO abstract out and use for initial setup to skip upload
            $requestBody = json_encode(
                array(
                    "countRemaining" => 0,
                    "comments" => array()
                )
            );
            $httpResponse = $this->makeHTTPRequest('POST', "$this->baseUrl/comments?token=$token", $requestBody);
            $this->log('debug', "Got POST /comments response status code=[$httpResponse->responseStatusCode]");
            $this->setSetupDone();
            return 0;
        }
        $this->log('warn', "Starting send comments loop. totalCommentCount=[$commentCount] lastSentId=[$lastSentId]");
        $getCommentsResponse = $this->getComments($lastSentId ? $lastSentId : -1);
        $countSynced = 0;
        if ($getCommentsResponse['status'] === 'success') {
            $count = count($getCommentsResponse['comments']);
            $this->log('warn', "Got comments to send count=[$count] from totalCount=[$commentCount] lastSendDate=[$lastSendDate] lastSentId=[$lastSentId]");
            $countRemaining = $commentCount;
            $chunkSize = 100;

            if ($countRemaining > 0) {
                $commentChunks = array_chunk($getCommentsResponse['comments'], $chunkSize);
                foreach ($commentChunks as $chunk) {
                    // for this chunk, attempt to send the whole thing. If it fails, split it up.
                    $chunkAttemptsRemaining = 5;
                    $dynamicChunkSize = $chunkSize;
                    $dynamicChunks = array($chunk);
                    while ($chunkAttemptsRemaining > 0) {
                        processChunks:
                        foreach ($dynamicChunks as $dynamicChunk) {
                            $lastComment = $dynamicChunk[count($dynamicChunk) - 1];
                            $lastCommentFromDateTime = strtotime($lastComment['date']) * 1000;
                            $countRemainingIfSuccessful = $countRemaining - count($dynamicChunk);
                            $requestBody = json_encode(
                                array(
                                    "countRemaining" => $countRemainingIfSuccessful,
                                    "comments" => $dynamicChunk
                                )
                            );
                            $dynamicChunkSizeActual = count($dynamicChunk);
                            $httpResponse = $this->makeHTTPRequest('POST', "$this->baseUrl/comments?token=$token", $requestBody);
                            $this->log('debug', "Got POST /comments response status code=[$httpResponse->responseStatusCode] and chunk size $dynamicChunkSize (actual=$dynamicChunkSizeActual)");
                            if ($httpResponse->responseStatusCode === 200) {
                                $response = json_decode($httpResponse->responseBody);
                                if ($response->status === 'success') {
                                    foreach ($response->commentIds as $wpId => $fcId) {
                                        update_comment_meta((int)$wpId, 'fastcomments_id', $fcId);
                                    }
                                    $countRemaining = $countRemainingIfSuccessful;
                                    $fromDateTime = $lastCommentFromDateTime;
                                    $this->setSettingValue('fastcomments_stream_last_send_timestamp', $fromDateTime, false);
                                    $this->setSettingValue('fastcomments_stream_last_send_id', $lastComment['externalId'], false);
                                    if ($countRemaining <= 0) {
                                        $this->setSetupDone();
                                    }
                                }
                                $chunkAttemptsRemaining = 0; // done
                            } else if ($httpResponse->responseStatusCode === 413 && $dynamicChunkSize > 1) {
                                $this->log('debug', "$dynamicChunkSize too big, splitting.");
                                $dynamicChunkSize = (int)($dynamicChunkSize / 10);
                                $dynamicChunks = array_chunk($chunk, max($dynamicChunkSize, 1));
                                $chunkAttemptsRemaining--;
                                if ($chunkAttemptsRemaining > 0) {
                                    goto processChunks; // break out of the dynamic chunks loop and run it again. yes goto is terrible but lot of work to refactor/test this.
                                }
                            }
                        }
                        $chunkAttemptsRemaining--;
                    }
                }
            } else {
                $this->setSetupDone();
            }
            $countSynced = $count;
        } else {
            $status = $getCommentsResponse['status'];
            $comments = $getCommentsResponse['comments'];
            $this->log('error', "Failed to get comments to send: status=[$status] comments=[$comments]");
        }
        // setting the lock to 1 second out causes each chunk upload to wait 59 seconds... so let's always clear it
        // we fixed issues with lock state being cached, and added a de-dupe mechanism in the backend to detect duplicate chunks, so race conditions should not be an issue.
        $this->clearLock("commandSendComments");
        $this->log('debug', 'Done sending comments');
        return $countSynced;
    }

    public function commandSetSyncDone() {
        $this->setSetupDone();
    }

    private function setSetupDone() {
        /**
         * Note - important we don't set the last stream fetch timestamp here to now(), because our timestamps
         * will be different than the server's, and that has no impact on setting the setup as done anyway.
         * The fastcomments_stream_last_fetch_timestamp should be set whenever we actually fetch the stream.
         */
        $this->setSettingValue('fastcomments_setup', true);
        $this->clearLock("commandSendComments");
    }

}
