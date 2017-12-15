<?php

namespace core\controllers;

use core\engine\Application;
use core\engine\Utility;
use core\engine\Router;
use core\models\Coin;

class Coin_controller
{
    static public $initialized = false;

    const SETRATES_URL = 'coin/setrates';

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
            self::handleSetRatesRequest();
        }, self::SETRATES_URL, Router::POST_METHOD);
    }

    static public function handleSetRatesRequest()
    {
        foreach (array_merge(Coin::coins(), [Coin::token()]) as $coin) {
            if (isset($_POST[$coin])) {
                $rate = (double)$_POST[$coin];
                Coin::setRate($coin, $rate);
            }
        }
        Utility::location(Administrator_controller::SETTINGS);
    }
}