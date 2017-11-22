<?php

namespace core\engine;

use core\models\Investor;

class Application
{
    const VERSION = 1;

    static public function init()
    {
        define('PATH_TO_WORKING_DIR', __DIR__ . '/../../working_dir');
        define('PATH_TO_TMP_DIR', __DIR__ . '/../../working_dir/tmp');
        define('PATH_TO_THIRD_PARTY_DIR', __DIR__ . '/../../third_party');

        Configuration::requireLoadConfigFromFile(PATH_TO_WORKING_DIR . '/config.json');

        define('PATH_TO_PHPERRORS', PATH_TO_TMP_DIR . '/php-errors.log');

        mb_internal_encoding('UTF-8');
        date_default_timezone_set('Etc/GMT0');

        self::initTmpDir();
        self::initErrorHandling();

        if (@Application::getValue('version') !== self::VERSION) {
            self::db_initializing();
            Investor::db_initializing();
        }
    }

    static private function initTmpDir()
    {
        if (!is_dir(PATH_TO_TMP_DIR)) {
            mkdir(PATH_TO_TMP_DIR, 0777, true);
            chmod(PATH_TO_TMP_DIR, 0777);
        }
    }

    static private function initErrorHandling()
    {
        error_reporting(E_ALL | E_STRICT);
        ini_set('display_errors', 0);
        ini_set('log_error', 1);
        ini_set('error_log', PATH_TO_PHPERRORS);
        if (!is_file(PATH_TO_PHPERRORS)) {
            file_put_contents(PATH_TO_PHPERRORS, "error file initializing\r\n", FILE_APPEND);
            chmod(PATH_TO_PHPERRORS, 0777);
        }
        register_shutdown_function(function () {
            $lastError = error_get_last();
            if ($lastError && $lastError['type'] === E_ERROR) {
                $error = "Server internal error: {$lastError['message']} ({$lastError['line']})";
                die($error);
            }
        });
    }

    /**
     * @param string $key
     * @return mixed
     */
    static public function getValue($key)
    {
        $value = @DB::get("SELECT `value` FROM `key_value_storage` WHERE `key`=? LIMIT 1;", [$key])[0]['value'];
        if ($value) {
            return @json_decode($value, true);
        }
        return null;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    static public function setValue($key, $value)
    {
        DB::set("
            REPLACE `key_value_storage` SET `key`=?, `value`=?",
            [$key, $value]
        );
    }

    static private function db_initializing()
    {
        DB::query("
            CREATE TABLE IF NOT EXISTS `key_value_storage`  (
                `key` varchar(256) NOT NULL,
                `value` varchar(4096) NOT NULL,
                PRIMARY KEY (`key`)
            );
        ");
        self::setValue('version', self::VERSION);
    }

    const ENCRYPTED_METHOD = 'AES-256-CBC';

    /**
     * @param mixed $data json-coverable data
     * @return string|false
     */
    static public function encodeData($data)
    {

        $string = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!$string) {
            return false;
        }
        $iv = substr(hash('sha256', APPLICATION_ID), 0, 16);

        $encryptedString = openssl_encrypt($string, self::ENCRYPTED_METHOD, APPLICATION_ID, 0, $iv);
        if (!$encryptedString) {
            return false;
        }
        $encryptedString = base64_encode($encryptedString);

        return $encryptedString;
    }

    /**
     * @param string $encryptedString
     * @return mixed|false
     */
    static public function decodeData($encryptedString)
    {
        $iv = substr(hash('sha256', APPLICATION_ID), 0, 16);
        $decryptedString = @openssl_decrypt(base64_decode($encryptedString), self::ENCRYPTED_METHOD, APPLICATION_ID, 0, $iv);
        if (!$decryptedString) {
            return false;
        }
        $data = json_decode($decryptedString, true);
        if (!$data) {
            return false;
        }
        return $data;
    }
}