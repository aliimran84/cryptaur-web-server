<?php

namespace core\models;

use core\engine\Application;

class Coin
{
    const ETH_COIN = 'ETH';
    const ETC_COIN = 'ETC';
    const BTC_COIN = 'BTC';
    const BTG_COIN = 'BTG';
    const BCC_COIN = 'BCC';
    const DOGE_COIN = 'DOGE';

    const COINS = [
        self::ETH_COIN,
        self::ETC_COIN,
        self::BTC_COIN,
        self::BTG_COIN,
        self::BCC_COIN,
        self::DOGE_COIN
    ];

    const MIN_CONF = [
        self::ETH_COIN => 12,
        self::ETC_COIN => 10,
        self::BTC_COIN => 3,
        self::BTG_COIN => 10,
        self::BCC_COIN => 10,
        self::DOGE_COIN => 6
    ];

    const TOKEN = 'CPT';

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
     * @return double count of usd in one coin
     */
    static public function getRate($coin)
    {
        $coin = strtoupper($coin);
        return Application::getValue(self::RATE_KEY_PREFIX . $coin);
    }

    /*
     * @param string $coin
     * @param int $conf
     * @return bool
     */
    static public function checkDepositConfirmation($coin, $conf)
    {
        return $conf >= Coin::MIN_CONF[$coin];
    }
}