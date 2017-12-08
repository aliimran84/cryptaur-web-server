<?php

namespace core\controllers;

use core\engine\Application;
use core\engine\Router;
use core\engine\Utility;
use core\views\Base_view;
use core\views\Dashboard_view;
use core\views\Menu_point;

class Dashboard_controller
{
    const BASE_URL = 'dashboard';

    static public function init()
    {
        Router::register(function () {
            if (!Application::$authorizedInvestor) {
                Utility::location();
            }
            Base_view::$TITLE = 'Dashboard';
            Base_view::$CONTENT_BLOCK_CLASSES[] = 'dashboard';
            Base_view::$MENU_POINT = Menu_point::Dashboard;
            echo Base_view::header();
            echo Dashboard_view::view();
            echo Base_view::footer();
        }, self::BASE_URL);
    }
}