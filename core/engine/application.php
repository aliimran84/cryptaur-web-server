<?php

namespace core\engine;

class Application
{
    static public function init()
    {
        define('PATH_TO_WORKING_DIR', __DIR__ . '/../../working_dir');
        define('PATH_TO_TMP_DIR', __DIR__ . '/../../working_dir/tmp');
        define('PATH_TO_THIRD_PARTY_DIR', __DIR__ . '/../../third_party');

        mb_internal_encoding('UTF-8');
        date_default_timezone_set('Etc/GMT0');

        self::initErrorHandling();
    }

    static public function initErrorHandling()
    {
        error_reporting(E_ALL | E_STRICT);
        ini_set('display_errors', 0);
        ini_set('log_error', 1);
        if (!is_dir(PATH_TO_TMP_DIR)) {
            mkdir(PATH_TO_TMP_DIR, 0777, true);
            chmod(PATH_TO_TMP_DIR, 0777);
        }
        ini_set('error_log', PATH_TO_TMP_DIR . '/php-errors.log');
        register_shutdown_function(function () {
            $lastError = error_get_last();
            if ($lastError && $lastError['type'] === E_ERROR) {
                $error = "Server internal error: {$lastError['message']} ({$lastError['line']})";
                die($error);
            }
        });
    }
}