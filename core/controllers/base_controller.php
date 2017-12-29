<?php

namespace core\controllers;

use core\engine\Application;
use core\engine\Router;
use core\views\Base_view;
use core\views\Menu_point;
use core\views\Wallet_view;

class Base_controller
{
    static public $initialized = false;

    const ABOUT_URL = 'about';

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
}