<?php

namespace core\models;

use core\controllers\PaymentServer_controller;
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
                `balance` double(20, 8) UNSIGNED DEFAULT '0',
                `usd_used` double(20, 8) UNSIGNED DEFAULT '0',
                PRIMARY KEY (`id`)
            );
        ");
    }

    /**
     * @param double $amount
     * @param double $usdUsed
     */
    public function addToWallet($amount, $usdUsed)
    {
        // todo: is it not a event time? must not to put into wallet!

        $this->balance += $amount;
        $this->usdUsed += $usdUsed;
        DB::set("
            UPDATE `wallets`
            SET
                `balance` = ?,
                `usd_used` = ?
            WHERE
                `id` = ?
            LIMIT 1
        ;", [$this->balance, $this->usdUsed, $this->id]);
    }

    static private function createWithDataFromDB($data)
    {
        $instance = new Wallet();
        $instance->id = $data['id'];
        $instance->investor_id = $data['investor_id'];
        $instance->coin = $data['coin'];
        $instance->address = $data['address'];
        $instance->balance = $data['balance'];
        $instance->usdUsed = $data['usdUsed'];
        return $instance;
    }

    /**
     * @param int $investorId
     * @param string $coin
     * @param bool $withoutRegistration
     * @return Wallet|null
     */
    static public function getByInvestoridCoin($investorId, $coin, $withoutRegistration = false)
    {
        $wallet = @DB::get("
            SELECT * FROM `wallets`
            WHERE
                `investor_id` = ? AND
                `coin` = ?
            LIMIT 1
        ;", [$investorId, $coin])[0];

        if (!$wallet && !$withoutRegistration) {
            $regResult = self::requestWalletRegistration($investorId, $coin);
            if (is_bool($regResult)) {
                return null;
            }
        }

        return self::createWithDataFromDB($wallet);
    }

    /**
     * @param int $investorId
     * @return Wallet[]
     */
    static public function getByInvestorid($investorId)
    {
        $walletsData = @DB::get("
            SELECT * FROM `wallets`
            WHERE
                `investor_id` = ?
        ;", [$investorId]);
        $wallets = [];
        foreach ($walletsData as $walletData) {
            $wallets[] = self::createWithDataFromDB($walletData);
        }
        return $wallets;
    }

    /**
     * @param int $investorId
     * @param string $coin
     * @param string $address
     * @return Wallet
     */
    static public function registerWallet($investorId, $coin, $address)
    {
        $existing = @DB::get("
            SELECT * FROM `wallets`
            WHERE
                `investor_id` = ? AND
                `coin` = ?
            LIMIT 1
        ;", [$investorId, $coin])[0];
        if ($existing) {
            DB::set("
                UPDATE `wallets`
                SET
                    `address`= ? 
                WHERE
                    `investor_id` = ? AND
                    `coin` = ?
            LIMIT 1;", [$address, $investorId, $coin]);
        } else {
            DB::set("
                INSERT INTO `wallets`
                SET
                    `investor_id` = ?,
                    `coin` = ?,
                    `address`= ?
            ;", [$investorId, $coin, $address]);
        }

        return self::getByInvestoridCoin($investorId, $coin, true);
    }

    /**
     * send request. It can answer with address now or later
     * @param int $investorId
     * @param string $coin
     * @return bool|Wallet
     */
    static public function requestWalletRegistration($investorId, $coin)
    {
        $pServer = PaymentServer::getFirst();
        if (!$pServer) {
            return false;
        }

        $response = PaymentServer_controller::requestWalletRegistration($pServer, $coin, $investorId);
        if (!$response) {
            return false;
        }

        if ($response['pending'] || !$response['address']) {
            return true;
        }
        return self::registerWallet($investorId, $coin, $response['address']);
    }

    /**
     * @return double
     */
    static public function totalUsdUsed()
    {
        $usdUsed = (double)@DB::get("
            SELECT SUM(`usd_used`) as `total_usdUsed` FROM `wallets`
        ;")[0]['total_usdUsed'];
        return $usdUsed;
    }
}