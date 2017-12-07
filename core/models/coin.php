<?php

namespace core\models;

use core\engine\Application;
use core\engine\Configuration;

class Coin
{
    /**
     * @param bool $onlyActivate
     * @return string[]
     */
    static public function coins($onlyActivate = true)
    {
        $coins = [];
        foreach (Configuration::$CONFIG['coins'] as $coin => $data) {
            if (!$onlyActivate || $data['activate']) {
                $coins[] = $coin;
            }
        }
        return $coins;
    }

    /**
     * @return string
     */
    static public function token()
    {
        return Configuration::$CONFIG['token']['name'];
    }

    const RATE_KEY_PREFIX = 'rate_count_of_usd_in_';

    /**
     * @param string $coin
     * @param double $rate count of usd in one coin
     */
    static public function setRate($coin, $rate)
    {
        $coin = strtoupper($coin);
        Application::setValue(self::RATE_KEY_PREFIX . $coin, $rate);
    }

    /**
     * @param string $coin
     * @return null|double count of usd in one coin
     */
    static public function getRate($coin)
    {
        $coin = strtoupper($coin);
        return Application::getValue(self::RATE_KEY_PREFIX . $coin);
    }

    /*
     * @param string $coin
     * @return bool
     */
    static public function issetCoin($coin)
    {
        return isset(Configuration::$CONFIG['coins'][$coin]);
    }

    /*
     * @param string $coin
     * @param int $conf
     * @return bool
     */
    static public function checkDepositConfirmation($coin, $conf)
    {
        if (!Coin::issetCoin($coin)) {
            return false;
        }
        return $conf >= Configuration::$CONFIG['coins'][$coin]['min_conirmation'];
    }
}