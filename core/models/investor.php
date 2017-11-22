<?php

namespace core\models;

use core\engine\DB;

class Investor
{
    public $id = 0;
    public $referrer_id = 0;
    public $joined_datetime = 0;
    public $email = '';
    public $tokens_count = 0;
    public $eth_address = '';
    public $eth_withdrawn = 0;

    static public function db_initializing()
    {
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors`  (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `referrer_id` int(0) UNSIGNED NOT NULL,
                `joined_datetime` datetime(0) NOT NULL,
                `email` varchar(254) NOT NULL,
                `eth_address` varchar(50) NOT NULL,
                `eth_withdrawn` bigint(20) NOT NULL,
                `tokens_count` bigint(20) UNSIGNED NOT NULL,
                PRIMARY KEY (`id`)
            );
        ");
    }
}