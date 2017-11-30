<?php

namespace core\engine;

use core\controllers\Coin_controller;
use core\controllers\Dashboard_controller;
use core\controllers\Deposit_controller;
use core\controllers\Administrator_controller;
use core\controllers\Investor_controller;
use core\controllers\PaymentServer_controller;
use core\models\Administrator;
use core\models\Deposit;
use core\models\Investor;
use core\models\PaymentServer;
use core\models\Wallet;
use core\views\Base_view;
use core\views\Wallet_view;

class Application
{
    const VERSION = 1;

    /**
     * @var Investor|null
     */
    static public $authorizedInvestor = null;
    static public $authorizedAdministrator = null;

    static public function init()
    {
        define('PATH_TO_WORKING_DIR', __DIR__ . '/../../working_dir');
        define('PATH_TO_TMP_DIR', __DIR__ . '/../../working_dir/tmp');
        define('PATH_TO_THIRD_PARTY_DIR', __DIR__ . '/../../third_party');
        define('PATH_TO_WEB_ROOT_DIR', __DIR__ . '/../../web_root');

        Configuration::requireLoadConfigFromFile(PATH_TO_WORKING_DIR . '/config.json');

        define('PATH_TO_PHPERRORS', PATH_TO_TMP_DIR . '/php-errors.log');

        mb_internal_encoding('UTF-8');
        date_default_timezone_set('Etc/GMT0');

        self::initTmpDir();
        self::initErrorHandling();

        if (!@Application::getValue('version')) {
            self::db_init();
            Investor::db_init();
            Wallet::db_init();
            Deposit::db_init();
            Administrator::db_init();
            PaymentServer::db_init();
        }

        Router::registerDefault(function () {
            echo Base_view::header();
            echo Base_view::about_stageOne();
            if (Application::$authorizedInvestor) {
                echo Wallet_view::newContribution();
            }
            echo Base_view::footer();
        });

        Investor_controller::init();
        Administrator_controller::init();
        PaymentServer_controller::init();
        Dashboard_controller::init();
        Coin_controller::init();
        Deposit_controller::init();
    }

    static private function initTmpDir()
    {
        if (!is_dir(PATH_TO_WORKING_DIR)) {
            mkdir(PATH_TO_WORKING_DIR, 0777, true);
            chmod(PATH_TO_WORKING_DIR, 0777);
        }
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
        if (!is_null($value)) {
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

    static private function db_init()
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
}