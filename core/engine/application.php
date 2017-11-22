<?php

namespace core\engine;

class Application
{
    static public function init()
    {
        define('PATH_TO_WORKING_DIR', __DIR__ . '/../../working_dir');
        define('PATH_TO_TMP_DIR', __DIR__ . '/../../working_dir/tmp');
        define('PATH_TO_THIRD_PARTY_DIR', __DIR__ . '/../../third_party');

        define('PATH_TO_PHPERRORS', PATH_TO_TMP_DIR . '/php-errors.log');

        mb_internal_encoding('UTF-8');
        date_default_timezone_set('Etc/GMT0');

        self::initTmpDir();
        self::initErrorHandling();
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
}