<?php

namespace core\controllers;

use core\engine\Application;
use core\engine\DB;
use core\engine\Router;
use core\engine\Utility;
use core\views\Base_view;
use core\views\Menu_point;
use core\views\Wallet_view;

class Base_controller
{
    static public $initialized = false;

    const ABOUT_URL = 'about';

    const ICOINFO_FILE = PATH_TO_TMP_DIR . '/ico_info.json';
    const ICOINFO_UPDATE_INTERVAL = 150;

    static public function init()
    {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        Router::register(function () {
            self::handleAbout();
        }, self::ABOUT_URL);
    }

    static private function handleAbout()
    {
        Base_view::$MENU_POINT = Menu_point::About;
        echo Base_view::header();
        echo Base_view::about_stageOne();
        if (Application::$authorizedInvestor) {
            echo Wallet_view::newContribution();
        }
        echo Base_view::footer();
    }

    static public function icoInfo($forceSetNewValues)
    {
        $tokens = 0;
        $eth = 0;
        $btc = 0;
        $users_count = 0;

        $needUpdate = true;
        if (!$forceSetNewValues && is_file(self::ICOINFO_FILE)) {
            $data = @json_decode(file_get_contents(self::ICOINFO_FILE), true);
            if ($data && isset($data['datetime'])) {
                if (time() - $data['datetime'] < self::ICOINFO_UPDATE_INTERVAL) {
                    $needUpdate = false;
                    $tokens = $data['total_tokens'];
                    $eth = $data['total_eth'];
                    $btc = $data['total_btc'];
                    $users_count = $data['total_users'];
                }
            }
        }

        if ($needUpdate) {
            $tokens = (int)@DB::get("SELECT SUM(`tokens_count`) as `total_tokens` FROM `investors`;")[0]['total_tokens'];
            $eth = Utility::int_string(DB::get("SELECT SUM(`balance`) AS `sum` FROM `wallets` WHERE `coin`='eth';")[0]['sum']);
            $btc = Utility::int_string(DB::get("SELECT SUM(`balance`) AS `sum` FROM `wallets` WHERE `coin`='btc';")[0]['sum']);
            $users_count = Utility::int_string(DB::get("SELECT COUNT(*) AS `count` FROM `investors`;")[0]['count']);

            file_put_contents(self::ICOINFO_FILE, json_encode([
                'total_tokens' => $tokens,
                'total_eth' => $eth,
                'total_btc' => $btc,
                'total_users' => $users_count,
                'datetime' => time()
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }

        return [
            'total_tokens' => $tokens,
            'total_eth' => $eth,
            'total_btc' => $btc,
            'total_users' => $users_count
        ];
    }
}