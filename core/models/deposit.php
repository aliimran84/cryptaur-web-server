<?php

namespace core\models;

use core\engine\DB;
use core\engine\Utility;

class Deposit
{
    public $id = 0;
    public $coin = '';
    public $txid = '';
    public $amount = 0;
    public $usd = 0;
    public $datetime = 0;
    public $investor_id = 0;
    public $used_in_minting = 0;
    public $used_in_bounty = 0;

    static public function db_init()
    {
        DB::query("
            CREATE TABLE IF NOT EXISTS `deposits` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `investor_id` int(10) UNSIGNED NOT NULL,
                `coin` varchar(32) NOT NULL,
                `txid` varchar(254) NOT NULL,
                `vout` int(10) NOT NULL,
                `amount` double(20, 8) NOT NULL,
                `usd` double(20, 8) NOT NULL,
                `rate` double(20, 8) NOT NULL,
                `datetime` datetime(0) NOT NULL,
                `used_in_minting` tinyint(1) UNSIGNED NOT NULL,
                `used_in_bounty` tinyint(1) UNSIGNED NOT NULL,
                PRIMARY KEY (`id`)
            );
        ");
    }

    static public function receiveDeposit($amount, $coin, $conf, $txid, $vout, $investorId)
    {
        Utility::logOriginalRequest('paymentServerDeposit/' . time());
    }
}