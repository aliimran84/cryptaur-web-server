<?php

namespace core\models;

use core\engine\DB;

class Wallet
{
    public $id = 0;
    public $investor_id = 0;
    public $coin = '';
    public $address = '';
    public $balance = 0;
    public $usd = 0;

    static public function db_init()
    {
        DB::query("
            CREATE TABLE IF NOT EXISTS `wallets` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `investor_id` int(10) UNSIGNED NOT NULL,
                `coin` varchar(32) NOT NULL,
                `address` varchar(254) NOT NULL,
                `balance` double(20, 8) UNSIGNED NOT NULL,
                `usd` double(20, 8) UNSIGNED NOT NULL,
                PRIMARY KEY (`id`)
            );
        ");
    }
}