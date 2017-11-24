<?php

namespace core\controllers;

use core\engine\Application;
use core\engine\Router;
use core\engine\Utility;
use core\views\Base_view;
use core\views\Transactions_view;

class Transactions_controller
{
    const BASE_URL = 'transactions';

    static public function init()
    {
        Router::register(function () {
            if (!Application::$authorizedInvestor) {
                Utility::location();
            }
            echo Base_view::header('Transaction');
            echo Transactions_view::view();
            echo Base_view::footer();
        }, self::BASE_URL);
    }
}