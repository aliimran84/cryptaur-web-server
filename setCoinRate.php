<?php

require __DIR__ . '/loader.php';

use core\engine\Application;
use core\engine\Utility;
use core\models\Coin;

Application::init();

$coinsData = @json_decode(file_get_contents('https://api.coinmarketcap.com/v1/ticker/'), true);
if ($coinsData && is_array($coinsData)) {
    foreach (Coin::coins() as $coin) {
        foreach ($coinsData as $coinData) {
            if (strtoupper(@$coinData['symbol']) === strtoupper($coin)) {
                $price = (double)@$coinData['price_usd'];
                if ($price > 0) {
                    Coin::setRate($coin, $price);
                }
            }
        }
    }
}