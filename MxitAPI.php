<?php

/**
 * MXit API PHP Wrapper - version 1.2.4
 *
 * Written by: Ashley Kleynhans <ashley@mxit.com>
 *
 * Copyright 2012 MXit Lifestyle (Pty) Ltd.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */

/**
 * Ensure that CURL and JSON PHP extensions are present
 */
if (!function_exists('curl_init')) {
  throw new Exception('The Mxit PHP class is unable to find the CURL PHP extension.');
}

if (!function_exists('json_decode')) {
  throw new Exception('The Mxit PHP class is unable to find the JSON PHP extension.');
}

function base64url_decode($base64url) {
    $base64 = strtr($base64url, '-_', '+/');
    $plainText = base64_decode($base64);
    return ($plainText);
}

function is_json($json) {
    json_decode($json);
    return (json_last_error() == JSON_ERROR_NONE);
}

class MxitAPI {
    private $_version;
    private $_app_key;
    private $_app_secret;
    private $_token_type;
    private $_access_token;
    private $_expires_in;
    private $_scope;
    private $_id_token;
    private $_headers;

    public $http_status;
    public $content_type;
    public $result;
    public $error;

    public function __construct($key, $secret) {
        $this->_version = '1.2.4';
        $this->_app_key = $key;
        $this->_app_secret = $secret;
        $this->error = FALSE;
    }

    private function _call_api($url, $method='POST', $params='', $decode=TRUE) {
        $this->http_status = NULL;
        $this->content_type = NULL;
        $this->result = NULL;
        $this->error = FALSE;

        $fields = '';

        if (($method == 'POST' || $method == 'PUT' || $method == 'DELETE') && $params != '') {
            $fields = (is_array($params)) ? http_build_query($params) : $params;
        }

        if ($method == 'PUT' || $method == 'POST' || $method == 'DELETE') {
            $this->_headers[] = 'Content-Length: '. strlen($fields);
        }

        $opts = array(
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_VERBOSE        => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT        => 60,
                CURLOPT_USERAGENT      => 'mxit-php-'. $this->_version,
                CURLOPT_URL            => $url,
                CURLOPT_HTTPHEADER     => $this->_headers
                );

        if (($method == 'POST' || $method == 'PUT' || $method == 'DELETE') && $params != '') {
            $opts[CURLOPT_POSTFIELDS] = $fields; 
        }
        
        if ($method == 'POST' && is_array($params)) {
            $opts[CURLOPT_POST] = count($params);
        } elseif ($method == 'PUT') {
            $opts[CURLOPT_CUSTOMREQUEST] = 'PUT';
        } elseif ($method == 'DELETE') {
            $opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        } elseif ($method == 'POST') {
            $opts[CURLOPT_POST] = TRUE;
        }

        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);
        $this->http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($this->http_status != 200) {
            // Problem with API call, we received an HTTP status code other than 200
            $this->error = TRUE;
        }

        $this->result = (($decode === TRUE) && (is_json($result) === TRUE)) ? json_decode($result) : $result;
    }

    private function _api_headers($format='json', $remove_expect=FALSE) {
        $this->_headers = array();
        $this->_headers[] = 'Content-type: application/'. $format;
        $this->_headers[] = 'Accept: application/'. $format;
        $this->_headers[] = 'Authorization: '. ucfirst($this->_token_type) .' '. $this->_access_token;

        if ($remove_expect === TRUE) {
            $this->_headers[] = 'Expect:';
        }
    }

    private function _check_scope($method, $scope) {
        if (strstr($this->_scope, $scope) === FALSE)
            throw new Exception('Invalid scope specified for '. $method .'() method, should be: '. $scope);

        if (!isset($this->_access_token) || !isset($this->_token_type) || !isset($this->_expires_in))
            throw new Exception('Access token is not set, obtain an access token using get_token()');
    }

    /**
     *   Requests the user to allow your application access to specific MXit information
     *
     *   Redirects back to redirect_uri/?code=f00df00df00df00df00df00df00df00d
     *
     *   Where the value of code (f00df00df00df00df00df00df00df00d in this example)
     *   is used to obtain the access token.
     */
    public function request_access($redirect_uri, $scope, $state='') {
        $url = "https://auth.mxit.com/authorize?response_type=code&client_id=". $this->_app_key;
        $url .= "&redirect_uri=". urlencode($redirect_uri);
        $url .= "&scope=". urlencode($scope);

        if ($state != '')
            $url .= '&state='. urlencode($state);

        header('Location: '. $url);
        exit;
    }

    /**
     *   Authenticates the user against their MXit credentials
     *
     *   SUCCESS:
     *   Redirects back to redirect_uri/?code=f00df00df00df00df00df00df00df00d
     *
     *   FAILURE:
     *   Redirects back to redirect_uri/?error=access_denied
     *
     *   Where the value of code (f00df00df00df00df00df00df00df00d in this example)
     *   is used to obtain the access token.
     */
    public function authenticate($redirect_uri, $state='') {
        $this->request_access($redirect_uri, 'openid', $state);  
    }

    /**
     *   Checks the authentication status from the authentication server callback
     */
    public function authentication_status() {
        if (isset($_GET['code'])) {
            $authenticated = TRUE;
        } else {
            $authenticated = FALSE;
            $this->error = TRUE;
        }

        return $authenticated;
    }

    /**
     *   Request the actual token from the OAuth2 server
     */
    public function get_user_token($code, $redirect_uri) {
        $url = "https://auth.mxit.com/token";

        $params = array('grant_type'   => 'authorization_code',
                        'code'         => $code,
                        'redirect_uri' => $redirect_uri);

        $this->_headers = array();
        $this->_headers[] = 'Authorization: Basic '. base64_encode($this->_app_key .':'. $this->_app_secret);
        $this->_headers[] = "Content-Type: application/x-www-form-urlencoded";

        $this->_call_api($url, 'POST', $params);

        if ($this->error === FALSE) {
            $this->_access_token = $this->result->access_token;
            $this->_token_type = $this->result->token_type;
            $this->_expires_in = $this->result->expires_in;
            $this->_refresh_token = isset($this->result->refresh_token) ? $this->result->refresh_token : null;
            $this->_scope = $this->result->scope;

            // Only applicable to OpenID token requests
            if (isset($this->result->id_token))
                $this->_id_token = $this->result->id_token;
        }
    }

    /**
     *   Get an access token for an application, to perform an API Request
     */
    public function get_app_token($scope, $grant_type='client_credentials', $username='', $password='') {
        $url = "https://auth.mxit.com/token";

        $this->_headers = array();
        $this->_headers[] = 'Authorization: Basic '. base64_encode($this->_app_key .':'. $this->_app_secret);
        $this->_headers[] = "Content-Type: application/x-www-form-urlencoded";

        $params = array('grant_type' => $grant_type,
                        'scope'      => $scope);

        if ($grant_type == 'password') {
            $params['username'] = $username;
            $params['password'] = $password;
        }

        $this->_scope = $scope;
        $this->_call_api($url, 'POST', $params);

        if ($this->error === FALSE) {
            $this->_access_token = $this->result->access_token;
            $this->_token_type = $this->result->token_type;
            $this->_expires_in = $this->result->expires_in;
            $this->_refresh_token = isset($this->result->refresh_token) ? $this->result->refresh_token : null;
        }
    }

    /**
     *   Get access token detail
     */
    public function get_token() {
        $detail = array('access_token'  => $this->_access_token,
                        'token_type'    => $this->_token_type,
                        'expires_in'    => $this->_expires_in,
                        'refresh_token' => $this->_refresh_token,
                        'scope'         => $this->_scope);

        return $detail;
    }

    /**
     *   Set access token detail
     */
    public function set_token($detail) {
        $this->_access_token = $detail['access_token'];
        $this->_token_type = $detail['token_type'];
        $this->_expires_in = $detail['expires_in'];
        $this->_refresh_token = $detail['refresh_token'];
        $this->_scope = $detail['scope'];
    }

    /**
     *   Validate OpenID token
     */
    public function validate_token() {
        if (isset($this->_id_token)) {
            $token_parts = explode('.', $this->_id_token);

            $header = base64url_decode($token_parts[0]);
            $payload = base64url_decode($token_parts[1]);
            $signature = base64url_decode($token_parts[2]);

            if (is_json($header) && is_json($payload)) {
                $header = json_decode($header);
                $payload = json_decode($payload);

                if ($payload->aud != $this->_app_key)
                    return FALSE;

                // Time zone differences could potentially pose a problem, so disabling for now
                /*if ($payload->exp < time())
                    return FALSE;*/

                return $payload->user_id;
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

    /**
     *   ------------------------------------------------------------------------------
     *   The following methods are publicly available (no user authentication) required
     *   so you should use the get_app_token() method when working with them.
     *   -------------------------------------------------------------------------------
     */

    /**
     *   Get the users internally unique UserId for the provided MxitId or LoginName.
     *
     *   Url: http://api.mxit.com/user/lookup/{MXITID}
     *
     *   Application Token Required
     *
     *   Required scope: profile/public
     */
    public function get_user_id($login) {
        $this->_check_scope('get_user_id', 'profile/public');

        $url = "http://api.mxit.com/user/lookup/". $login;
        
        $this->_api_headers();
        $this->_call_api($url, 'GET');

        return $this->result;
    }

    /**
     *   Get the status message for the given MXitId.
     *
     *   Url: http://api.mxit.com/user/public/statusmessage/{MXITID}
     *
     *   Application Token Required
     *
     *   Required scope: profile/public
     */
    public function get_status($login) {
        $this->_check_scope('get_status', 'profile/public');

        $url = "http://api.mxit.com/user/public/statusmessage/". $login;
        
        $this->_api_headers();
        $this->_call_api($url, 'GET');

        return $this->result;  
    }

    /**
     *   Get the nickname of a MXit user with the given MXitId.
     *
     *   Url: http://api.mxit.com/user/public/displayname/{MXITID}
     *
     *   Application Token Required
     *
     *   Required scope: profile/public
     */
    public function get_display_name($login) {
        $this->_check_scope('get_display_name', 'profile/public');

        $url = "http://api.mxit.com/user/public/displayname/". $login;
        
        $this->_api_headers();
        $this->_call_api($url, 'GET');

        return $this->result;
    }

    /**
     *   Download the user avatar image for a MXit user with the given MXitId.
     *
     *   Url: http://api.mxit.com/user/public/avatar/{MXITID}
     *
     *   Application Token Required
     *
     *   Required scope: profile/public
     */
    public function get_avatar($login) {
        $this->_check_scope('get_avatar', 'profile/public');

        $url = "http://api.mxit.com/user/public/avatar/". $login;
        
        $this->_api_headers();
        $this->_call_api($url, 'GET', '', FALSE);

        return $this->result;   
    }

    /**
     *   Get the basic profile information for a MXit user with the given UserId.
     *
     *   Url: http://api.mxit.com/user/profile/{USERID}
     *
     *   Application Token Required
     *
     *   Required scope: profile/public
     *
     *   NOTE: This method requires the user's mxitid and NOT their login
     */
    public function get_basic_profile($mxitid) {
        $this->_check_scope('get_basic_profile', 'profile/public');

        $url = "http://api.mxit.com/user/profile/". $mxitid;
        
        $this->_api_headers();
        $this->_call_api($url, 'GET');

        return $this->result;
    }

    /**
     *   Send a message to one or more MXit users.
     *
     *   Url: http://api.mxit.com/message/send/
     *
     *   Application Token Required
     *
     *   Required scope: message/send
     */
    public function send_message($from, $to, $message, $contains_markup) {
        $this->_check_scope('send_message', 'message/send');

        $params = array('Body'              => $message,
                        'ContainsMarkup'    => $contains_markup,
                        'From'              => $from,
                        'To'                => $to);

        $url = "http://api.mxit.com/message/send/";
        
        $this->_api_headers();
        $this->_call_api($url, 'POST', json_encode($params));
    }


    /**
     *   ----------------------------------------------------------------------------------
     *   The following methods require authentication with the user's username and password
     *   -----------------------------------------------------------------------------------
     */

    /**
     *   Retrieves the full profile including the cellphone number and email address of the userid in the access token.
     *
     *   User Token Required
     *
     *   Required scope: profile/private
     */
    public function get_full_profile($bypass_scope_check=FALSE) {
        if ($bypass_scope_check === FALSE)
            $this->_check_scope('get_full_profile', 'profile/private');

        $url = "http://api.mxit.com/user/profile";
        
        $this->_api_headers();
        $this->_call_api($url, 'GET');

        return $this->result;
    }

    /**
     *   Update the profile information for a MXit user with the given UserId contained in the access token.
     *
     *   Url: http://api.mxit.com/user/profile
     *
     *   User Token Required
     *
     *   Required scope: profile/write
     */
    public function update_profile($data) {
        $this->_check_scope('update_profile', 'profile/write');

        $url = "http://api.mxit.com/user/profile";
        
        $this->_api_headers();
        $this->_call_api($url, 'PUT', json_encode($data));

        return $this->result;
    }

    /**
     *   Subscribes the MXit user with the given UserId, contained in the access token, to the
     *   service or sends an invite to another user. If {contact} is a Service, then the service
     *   is added and accepted. If {contact} is a MXitId, then an invite is sent to the other user.
     *
     *   User Token Required
     *
     *   Required scope: contact/invite
     *
     *   NOTE: This method requires the user's mxitid and NOT their login
     */     
    public function add_contact($mxitid) {
        $this->_check_scope('add_contact', 'contact/invite');

        $url = "http://api.mxit.com/user/socialgraph/contact/". $mxitid;
        
        $this->_api_headers();
        $this->_call_api($url, 'PUT');
    }

    /**
     *   Get the social graph information for a MXit user with the given UserId contained in the
     *   access token.
     * 
     *   Filters: @All, @Friends, @Apps, @Invites, @Connections (Default), @Rejected, @Pending, @Deleted, @Blocked
     *
     *   Url: http://api.mxit.com/user/socialgraph/contactlist?filter={FILTER}&skip={SKIP}&count={COUNT}
     *
     *   User Token Required
     *
     *   Required scope: graph/read
     *
     *   NOTE: You might expect count to return 2 items if you specify a value of 2, but it will return 3 items
     *         So you should treat the value similar to an array value, ie specify 1 if you want 2 results
     */
    public function get_contact_list($filter='', $skip=0, $count=0) {
        $this->_check_scope('get_contact_list', 'graph/read');

        $url = "http://api.mxit.com/user/socialgraph/contactlist";

        if ($filter != '')
            $url .= '?filter='. $filter;

        if ($skip != 0) {
            $url .= ($filter == '') ? '?' : '&';
            $url .= 'skip='. $skip;
        }

        if ($count != 0) {
            $url .= ($filter == '' && $skip == 0) ? '?' : '&';
            $url .= 'count='. $count;
        }

        $this->_api_headers();
        $this->_call_api($url, 'GET');

        return $this->result;
    }

    /**
     *   Get a list of friends the user might know for a MXit user with the given UserId contained in the access token.
     *
     *   Url: http://api.mxit.com/user/socialgraph/suggestions
     *
     *   User Token Required
     *
     *   Required scope: graph/read
     *
     *   FIXME: HTTP status 400 (Bad Request)
     */
    public function get_friend_suggestions() {
        $this->_check_scope('get_friend_suggestions', 'graph/read');

        $url = "http://api.mxit.com/user/socialgraph/suggestions";

        $this->_api_headers('xml');
        $this->_call_api($url, 'GET');

        return $this->result;
    }

    /**
     *   Set the status message for a MXit user with the given UserId contained in the access token.
     *
     *   Url: http://api.mxit.com/user/statusmessage
     *
     *   User Token Required
     *
     *   Required scope: status/write
     */
    public function set_status($message) {
        $this->_check_scope('set_status', 'status/write');

        $url = "http://api.mxit.com/user/statusmessage";

        $this->_api_headers();
        $this->_call_api($url, 'PUT', json_encode($message));   
    }

    /**
     *   Upload or create an avatar image for a MXit user with the given UserId contained in the access token.
     *
     *   Url: http://api.mxit.com/user/avatar
     *
     *   User Token Required
     *
     *   Required scope: avatar/write
     */
    public function set_avatar($base64_encoded_avatar) {
        $this->_check_scope('set_avatar', 'avatar/write');

        $url = "http://api.mxit.com/user/avatar";
        $xml = '<base64Binary xmlns="http://schemas.microsoft.com/2003/10/Serialization/">'. $base64_encoded_avatar .'</base64Binary>';

        $this->_api_headers('xml', TRUE);
        $this->_call_api($url, 'POST', $xml);
    }

    /**
     *   Delete a avatar image for a MXit user with the given UserId contained in the access token.
     *
     *   Url: http://api.mxit.com/user/avatar
     *
     *   User Token Required
     *
     *   Required scope: avatar/write
     */
    public function delete_avatar() {
        $this->_check_scope('delete_avatar', 'avatar/write');

        $url = "http://api.mxit.com/user/avatar";
        
        $this->_api_headers();
        $this->_call_api($url, 'DELETE');
    }

    /**
     *   Get the gallery root information for a MXit user with the given UserId contained in the access token.
     *
     *   Url: http://api.mxit.com/user/media/
     *
     *   User Token Required
     *
     *   Required scope: content/read
     */
    public function list_gallery_folders() {
        $this->_check_scope('list_gallery_folders', 'content/read');

        $url = "http://api.mxit.com/user/media/";

        $this->_api_headers();
        $this->_call_api($url, 'GET');

        return $this->result;
    }

    /**
     *   Get the list of content in the gallery folder for a user with the given UserId contained in the access token and FolderId.
     *
     *   Url: http://api.mxit.com/user/media/list/{FOLDERNAME}?skip={SKIP}&count={COUNT}
     *
     *   User Token Required
     *
     *   Required scope: content/read
     */
    public function list_gallery_items($folder, $skip=0, $count=0) {
        $this->_check_scope('list_gallery_items', 'content/read');

        $url = "http://api.mxit.com/user/media/list/". urlencode($folder);

        if ($skip != 0) {
            $url .= '?skip='. $skip;
        }

        if ($count != 0) {
            $url .= ($filter == '' && $skip == 0) ? '?' : '&';
            $url .= 'count='. $count;
        }

        $this->_api_headers();
        $this->_call_api($url, 'GET');

        return $this->result;
    }

    /**
     *   Creates a gallery folder for a MXit user with the given UserId contained in the access token.
     *
     *   Url: http://api.mxit.com/user/media/{FOLDERNAME}
     *
     *   User Token Required
     *
     *   Required scope: content/write
     */
    public function create_gallery_folder($folder) {
        $this->_check_scope('create_gallery_folder', 'content/write');

        $url = "http://api.mxit.com/user/media/". urlencode($folder);

        $this->_api_headers();
        $this->_call_api($url, 'POST');
    }

    /**
     *   Rename a gallery folder for a MXit user with the given UserId contained in the access token.
     *
     *   Url: http://api.mxit.com/user/media/{FOLDERNAME}
     *
     *   User Token Required
     *
     *   Required scope: content/write
     */
    public function rename_gallery_folder($source, $destination) {
        $this->_check_scope('rename_gallery_folder', 'content/write');
        
        $url = "http://api.mxit.com/user/media/". urlencode($source);

        $this->_api_headers();
        $this->_call_api($url, 'PUT', json_encode($destination));
    }


    /**
     *   Delete a gallery folder for a MXit user with the given UserId contained in the access token.
     *
     *   Url: http://api.mxit.com/user/media/{FOLDERNAME}
     *
     *   User Token Required
     *
     *   Required scope: content/write
     */
    public function delete_gallery_folder($folder) {
        $this->_check_scope('delete_gallery_folder', 'content/write');
        
        $url = "http://api.mxit.com/user/media/". urlencode($folder);

        $this->_api_headers();
        $this->_call_api($url, 'DELETE');
    }

    /**
     *   Download the content item in the user's gallery for a MXit user with the given UserId contained in the access token and given FileId.
     *
     *   Url: http://api.mxit.com/user/media/content/{FILEID}
     *
     *   User Token Required
     *
     *   Required scope: content/read
     */
    public function download_gallery_image($file_id) {
        $this->_check_scope('download_gallery_image', 'content/read');

        $url = "http://api.mxit.com/user/media/content/". $file_id;

        $this->_api_headers();
        $this->_call_api($url, 'GET', '', FALSE);

        return $this->result;
    }

    /**
     *   Upload a gallery file for a MXit user with the given UserId contained in the access token.
     *
     *   Url: http://api.mxit.com/user/media/file/{FOLDERNAME}?fileName={FILENAME}&mimeType={MIMETYPE}
     *
     *   User Token Required
     *
     *   Required scope: content/write
     */
    public function upload_gallery_image($folder, $filename, $mime_type, $base64_encoded_content) {
        $this->_check_scope('upload_gallery_image', 'content/write');

        $url = "http://api.mxit.com/user/media/file/". urlencode($folder) .'?fileName='. urlencode($filename) .'&mimeType='. urlencode($mime_type);
        $xml = '<base64Binary xmlns="http://schemas.microsoft.com/2003/10/Serialization/">'. $base64_encoded_content .'</base64Binary>';

        $this->_api_headers('xml', TRUE);
        $this->_call_api($url, 'POST', $xml);
    }

    /**
     *   Rename a gallery file for a MXit user with the given UserId contained in the access token.
     *
     *   Url: http://api.mxit.com/user/media/file/{FILEID}
     *
     *   User Token Required
     *
     *   Required scope: content/write
     */
    public function rename_gallery_image($file_id, $destination) {
        $this->_check_scope('rename_gallery_image', 'content/write');

        $url = "http://api.mxit.com/user/media/file/". urlencode($file_id);
        
        $this->_api_headers();
        $this->_call_api($url, 'PUT', json_encode($destination));
    }

    /**
     *   Rename a gallery file for a MXit user with the given UserId contained in the access token.
     *
     *   Url: http://api.mxit.com/user/media/file/{FILEID}
     *
     *   User Token Required
     *
     *   Required scope: content/write
     */
    public function delete_gallery_image($file_id) {
        $this->_check_scope('delete_gallery_image', 'content/write');

        $url = "http://api.mxit.com/user/media/file/". urlencode($file_id);
        
        $this->_api_headers();
        $this->_call_api($url, 'DELETE');
    }

}

?>
