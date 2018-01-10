<?php

namespace core\controllers;

use core\engine\Application;
use core\engine\DB;
use core\engine\Router;
use core\views\Base_view;
use core\views\Menu_point;
use core\views\Wallet_view;

class Base_controller
{
    static public $initialized = false;

    const ABOUT_URL = 'about';
    const ICOINFO_URL = 'ico/info';

    static public function init()
    {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        Router::register(function () {
            self::handleAbout();
        }, self::ABOUT_URL);

        Router::register(function () {
            self::handleIcoInfo();
        }, self::ICOINFO_URL);
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

    static private function handleIcoInfo()
    {
        $eth = DB::get("SELECT SUM(`balance`) AS `sum` FROM `wallets` WHERE `coin`='eth';")[0]['sum'];
        $btc = DB::get("SELECT SUM(`balance`) AS `sum` FROM `wallets` WHERE `coin`='btc';")[0]['sum'];
        echo json_encode([
            'total_eth' => $eth,
            'total_btc' => $btc
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}