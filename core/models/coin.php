<?php

namespace core\models;

class Coin
{
    const ETH_COIN = 'ETH';
    const ETC_COIN = 'ETC';
    const BTC_COIN = 'BTC';
    const BTG_COIN = 'BTG';
    const BCC_COIN = 'BCC';

    const COINS = [
        self::ETH_COIN,
        self::ETC_COIN,
        self::BTC_COIN,
        self::BTG_COIN,
        self::BCC_COIN
    ];
}