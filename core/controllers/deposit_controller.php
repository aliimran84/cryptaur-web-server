<?php

namespace core\controllers;

use core\engine\Application;
use core\engine\Utility;
use core\engine\Router;
use core\models\Deposit;

class Deposit_controller
{
    static public $initialized = false;

    const SET_URL = 'deposit/set';

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