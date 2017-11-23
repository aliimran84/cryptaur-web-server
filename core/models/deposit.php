<?php

namespace core\models;

use core\engine\Application;
use core\engine\DB;

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
                `amount` double(20, 8) NOT NULL,
                `usd` double(20, 8) NOT NULL,
                `datetime` datetime(0) NOT NULL,
                `used_in_minting` tinyint(1) UNSIGNED NOT NULL,
                `used_in_bounty` tinyint(1) UNSIGNED NOT NULL,
                PRIMARY KEY (`id`)
            );
        ");
    }

    /**
     * check that new nonce bigger than old and update to new
     * @param string $serverKey
     * @param int $newNonce
     * @return bool
     */
    static public function checkUpdateNonce($serverKey, $newNonce)
    {
        $storageKey = "payment_server_nonce_$serverKey";
        $nonce = Application::getValue($storageKey);
        if ($nonce) {
            if ($nonce >= $newNonce) {
                return false;
            }
        }
        Application::setValue($storageKey, $newNonce);
        return true;
    }
}