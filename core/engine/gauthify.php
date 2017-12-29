<?php

namespace core\gauthify;

class GAuthify
{
    const PATH_TO_GAUTHIFYERRORS = PATH_TO_TMP_DIR . '/gauthify-errors.log';
    
    const ACCESS_POINT = 'https://api.gauthify.com/v1/';
    private $api_key;
    private $headers;

    private function __construct()
    {
    }

    /**
     * singleton object
     * @var GAuthify
     */
    static private $_instance;

    /**
     * @return GAuthify
     */
    static private function inst()
    {
        if (is_null(self::$_instance)) {
            self::initializeErrorFile();
            self::$_instance = new self();
            self::$_instance->init(GAUTHIFY_TOKEN);
        }
        return self::$_instance;
    }

    /**
     * init key and headers
     */
    private function init($api_key)
    {
        $this->api_key = $api_key;
        $this->headers = array("Authorization: " . 'Basic ' . base64_encode(':' . $api_key),
            'User-Agent: GAuthify-PHP/v2.0'
        );
    }

    /*
     * Handles the API requests
     */
    static private function request_handler($type, $url_addon = '', $params = array())
    {
        $api = GAuthify::inst();
        $req_url = self::ACCESS_POINT . $url_addon;
        $type = strtoupper($type);
        $req = curl_init();
        curl_setopt($req, CURLOPT_URL, $req_url);
        curl_setopt($req, CURLOPT_CUSTOMREQUEST, $type);
        curl_setopt($req, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($req, CURLOPT_HTTPHEADER, $api->headers);
        curl_setopt($req, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($req, CURLOPT_TIMEOUT, 5);
        $resp = curl_exec($req);
        if (!$resp) {
            self::error('Execution Error (100)');
            return NULL;
        }
        $status_code = curl_getinfo($req, CURLINFO_HTTP_CODE);
        $json_resp = json_decode($resp, true);
        $status_error = NULL;
        switch ($status_code) {
            case 401:
                $status_error = 'ApiKeyError';
            case 402:
                $status_error = 'RateLimitError';
            case 406:
                $status_error = 'ParameterError';
            case 404:
                $status_error = 'NotFoundError';
            case 409:
                $status_error = 'ConflictError';
        }
        if(!is_null($status_error))
        {
            $response_code = isset($json_resp['error_code']) ? ' ('.$json_resp['error_code'].')' : "";
            self::error("$status_error ($status_code)$response_code");
            return NULL;
        }
        if (!$json_resp) {
            self::error("JSON parse error. Likely header size issue. (100)");
            return NULL;
        }
        return $json_resp['data'];
    }

    /*
     * Creates new user (replaces with new if already exists)
     */
    public static function create_user($unique_id, $display_name, $email = null, $sms_number = null, $voice_number = null, $meta = null)
    {
        $params = array('unique_id' => $unique_id, 'display_name' => $display_name);
        if ($email) {
            $params['email'] = $email;
        }
        if ($sms_number) {
            $params['sms_number'] = $sms_number;
        }
        if ($voice_number) {
            $params['voice_number'] = $voice_number;
        }
        if ($meta) {
            $params['meta'] = json_encode($meta);
        }
        $url_addon = 'users/';
        return self::request_handler('POST', $url_addon, $params);
    }
    
    /*
     * Updates user's fields for given unique_id
     */
    public static function update_user($unique_id, $email = null, $sms_number = null, $voice_number = null, $meta = null, $reset_key = false)
    {
        $params = array();
        if ($email) {
            $params['email'] = $email;
        }
        if ($sms_number) {
            $params['sms_number'] = $sms_number;
        }
        if ($voice_number) {
            $params['voice_number'] = $voice_number;
        }
        if ($meta) {
            $params['meta'] = json_encode($meta);
        }
        if ($reset_key) {
            $params['reset_key'] = 'true';
        }
        $url_addon = sprintf('users/%s/', $unique_id);
        return self::request_handler('PUT', $url_addon, $params);
    }

    /*
     * Deletes user given by unique_id
     */
    public static function delete_user($unique_id)
    {
        $url_addon = sprintf('users/%s/', $unique_id);
        return self::request_handler('DELETE', $url_addon);
    }

    /*
     * Retrieves a list of all users
     */
    public static function get_all_users()
    {
        return self::request_handler('GET', $url_addon = 'users/');
    }

    /*
     * Returns a single user
     */
    public static  function get_user($unique_id)
    {
        $url_addon = sprintf('users/%s/', $unique_id);
        return self::request_handler('GET', $url_addon);
    }

    /*
     * Checks authcode returns true/false depending on correctness
     */
    public static function check($unique_id, $otp, $otp_id)
    {
        $url_addon = 'check/';
        $params = array('unique_id' => $unique_id, 'otp' => $otp, 'otp_id' => $otp_id);
        $response = self::request_handler('POST', $url_addon, $params);
        if(!is_null($response) && isset($response['authenticated'])) {
            return $response['authenticated'];
        }
        else {
            return NULL;
        }
    }

    /*
     * Returns a single user by ezGAuth token
     */
    public static function get_user_by_token($token)
    {
        $url_addon = 'token/';
        $params = array('token' => $token);
        return self::request_handler('POST', $url_addon, $params);
    }
    
    /*
     * Sends email with the one time auth_code
     */
    public static function send_email($unique_id, $email = null)
    {
        $url_addon = 'email/';
        $params = array('unique_id' => $unique_id);
        if ($email) {
            $params['email'] = $email;
        }
        return self::request_handler('POST', $url_addon, $params);
    }
    
    /*
     * Sends text message to phone number with the one time auth_code
     */
    public static function send_sms($unique_id, $sms_number = null)
    {
        $url_addon = 'sms/';
        $params = array('unique_id' => $unique_id);
        if ($sms_number) {
            $params['sms_number'] = $sms_number;
        }
        return self::request_handler('POST', $url_addon, $params);
    }
    
    /*
     * Sends email with the one time auth_code
     */
    public static function send_voice($unique_id, $voice_number = null)
    {
        $url_addon = 'voice/';
        $params = array('unique_id' => $unique_id);
        if ($voice_number) {
            $params['voice_number'] = $voice_number;
        }
        return self::request_handler('POST', $url_addon, $params);
    }
    /*
     * Returns array containing api errors.
     */
    public static function api_errors()
    {
        $url_addon = "errors/";
        return self::request_handler('GET', $url_addon);
    }

    static private function initializeErrorFile()
    {
        if (!is_file(self::PATH_TO_GAUTHIFYERRORS)) {
            self::error('error file initializing');
            chmod(self::PATH_TO_GAUTHIFYERRORS, 0777);
        }
    }

    /**
     * Текст ошибки в лог
     * @param string $text текст ошибки
     * @return string
     */
    static private function error($text)
    {
        $datetime = self::timetostr(time());
        file_put_contents(self::PATH_TO_GAUTHIFYERRORS, "\r\n$datetime\r\n$text\r\n", FILE_APPEND);
        return $text;
    }

    /**
     * @param int $timestamp time()
     * @return string строка в формате для mysqli
     */
    public static function timetostr($timestamp)
    {
        return date('Y-m-d H:i:s', $timestamp);
    }
}
