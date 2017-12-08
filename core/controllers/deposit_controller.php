<?php

namespace core\controllers;

use core\engine\Application;
use core\engine\Utility;
use core\engine\Router;
use core\models\Deposit;
use core\views\Base_view;
use core\views\Deposit_view;
use core\views\Menu_point;

class Deposit_controller
{
    static public $initialized = false;

    const SET_URL = 'deposit/set';
    const TRANSACTIONS_URL = 'transactions';

    static public function init()
    {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        Router::register(function () {
            if (!Application::$authorizedAdministrator) {
                Utility::location();
            }
            self::handleSetRequest();
        }, self::SET_URL, Router::POST_METHOD);

        Router::register(function () {
            if (!Application::$authorizedInvestor) {
                Utility::location();
            }
            Base_view::$TITLE = 'Transaction';
            Base_view::$MENU_POINT = Menu_point::Transactions;
            echo Base_view::header();
            echo Deposit_view::transactions();
            echo Base_view::footer();
        }, self::TRANSACTIONS_URL);
    }

    static public function handleSetRequest()
    {
        foreach ([Deposit::MINIMAL_TOKENS_FOR_MINTING_KEY, Deposit::MINIMAL_TOKENS_FOR_BOUNTY_KEY] as $key) {
            $value = (double)$_POST[$key];
            Application::setValue($key, $value);
        }
        Utility::location(Administrator_controller::COINS_SETTINGS);
    }
}