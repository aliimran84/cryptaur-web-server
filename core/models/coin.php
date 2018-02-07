<?php

namespace core\models;

use core\engine\Application;
use core\engine\Configuration;
use core\engine\DB;

class Coin
{
    const COMMON_COIN = 'ETH';
    const USD = 'USD';

    static private $rateStorage = [];

    static public function db_init()
    {
        DB::query("
            CREATE TABLE IF NOT EXISTS `coin_rate` (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `coin` VARCHAR(6) NOT NULL,
                `rate` DOUBLE(20,8) NOT NULL DEFAULT '-1',
                `time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            )
            DEFAULT CHARSET utf8
            DEFAULT COLLATE utf8_general_ci
        ;");
    }

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

    /**
     * @return string
     */
    static public function reinvestToken()
    {
        return Configuration::$CONFIG['token']['name'] . '_reinvest';
    }

    const RATE_KEY_PREFIX = 'rate_count_of_usd_in_';

    /**
     * @param string $coin
     * @param double $rate count of usd in one coin
     */
    static public function setRate($coin, $rate)
    {
        $coin = strtoupper($coin);
        self::$rateStorage[$coin] = $rate;
        Application::setValue(self::RATE_KEY_PREFIX . $coin, $rate);
        DB::set("
            INSERT INTO `coin_rate` 
            (`coin`,`rate`)
            VALUES
            (?,?);",
            [$coin, $rate]
        );
    }

    /**
     * how much coins in 1 $. tokens = usd / rate
     * @param string $coin
     * @return null|double count of usd in one coin
     */
    static public function getRate($coin)
    {
        $coin = strtoupper($coin);
        if (isset(self::$rateStorage[$coin])) {
            return self::$rateStorage[$coin];
        }
        $rate = Application::getValue(self::RATE_KEY_PREFIX . $coin);
        self::$rateStorage[$coin] = $rate;
        return $rate;
    }

    /**
     * @param double $amount
     * @param string $from
     * @param string $to
     * @return null|double
     */
    static public function convert($amount, $from, $to)
    {
        $from = strtoupper($from);
        $to = strtoupper($to);
        if ($from === $to) {
            return $amount;
        } else if ($from === self::USD) {
            $rate = Coin::getRate($to);
            if (is_null($rate)) {
                return null;
            }
            return $amount / $rate;
        } else if ($to === self::USD) {
            $rate = Coin::getRate($from);
            if (is_null($rate)) {
                return null;
            }
            return $amount * $rate;
        } else {
            $rate1 = Coin::getRate($from);
            $rate2 = Coin::getRate($to);
            if (is_null($rate1) || is_null($rate2)) {
                return null;
            }
            $usd = $amount * $rate1;
            return $usd / $rate2;
        }
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