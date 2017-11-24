<?php

namespace core\models;

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
}