<?php

namespace core\models;

use core\engine\DB;
use core\engine\Utility;

class Wallet
{
    public $id = 0;
    public $investor_id = 0;
    public $coin = '';
    public $address = '';
    public $balance = 0;
    public $usdUsed = 0;

    static public function db_init()
    {
        DB::query("
            CREATE TABLE IF NOT EXISTS `wallets` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `investor_id` int(10) UNSIGNED NOT NULL,
                `coin` varchar(32) NOT NULL,
                `address` varchar(254) NOT NULL,
                `balance` double(20, 8) UNSIGNED NOT NULL,
                `usdUsed` double(20, 8) UNSIGNED NOT NULL,
                PRIMARY KEY (`id`)
            );
        ");
    }

    static public function getByInvestoridCoin($investorId, $coin)
    {
        $wallet = @DB::get("
            SELECT * FROM `wallets`
            WHERE
                `investor_id` = ?,
                `coin` = ?
            LIMIT 1
        ;", [$investorId, $coin])[0];

        if (!$wallet) {
            self::requestWalletRegistration($investorId, $coin);
            return null;
        }

        $instance = new Wallet();
        $instance->id = $wallet['id'];
        $instance->investor_id = $wallet['investor_id'];
        $instance->coin = $wallet['coin'];
        $instance->address = $wallet['address'];
        $instance->balance = $wallet['balance'];
        $instance->usdUsed = $wallet['usdUsed'];
    }

    /**
     * @param int $investorId
     * @param string $coin
     * @param string $address
     */
    static public function registerWallet($investorId, $coin, $address)
    {
        $existing = @DB::get("
            SELECT * FROM `wallets`
            WHERE
                `investor_id` = ?,
                `coin` = ?
            LIMIT 1
        ;", [$investorId, $coin])[0];
        if ($existing) {
            DB::set("
                UPDATE `wallets`
                SET
                    `address`= ? 
                WHERE
                    `investor_id` = ?,
                    `coin` = ?
            LIMIT 1;", [$address, $investorId, $coin]);
        } else {
            DB::set("
                INSERT INTO `wallets`
                SET
                    `investor_id` = ?,
                    `coin` = ?,
                    `address`= ?
            ;", [$address, $investorId, $coin]);
        }
    }

    /**
     * send request. It can answer with address now or later
     * @param int $investorId
     * @param string $coin
     * @return bool
     */
    static public function requestWalletRegistration($investorId, $coin)
    {
        $pServer = PaymentServer::getFirst();
        if (!$pServer) {
            return false;
        }
        $response = @json_decode(
            Utility::httpPost("$pServer/$coin/getaddress", ['user' => $investorId]),
            true
        );
        if (!isset($response['pending']) || !isset($response['address'])) {
            return false;
        }
        if ($response['pending'] || !$response['address']) {
            return true;
        }
        self::registerWallet($investorId, $coin, $response['address']);
        return true;
    }
}