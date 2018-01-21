<?php

namespace core\models;

use core\controllers\PaymentServer_controller;
use core\engine\Application;
use core\engine\DB;
use core\engine\Utility;

class Wallet
{
    public $id = 0;
    public $investor_id = 0;
    public $coin = '';
    public $address = '';
    public $balance = 0;
    public $usd_used = 0;

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
            )
            DEFAULT CHARSET utf8
            DEFAULT COLLATE utf8_general_ci
        ;");
    }

    /**
     * @param double $amount
     * @param double $usdUsed
     */
    public function addToWallet($amount, $usdUsed)
    {
        // todo: is it not a event time? must not to put into wallet!

        $this->balance += $amount;
        $this->usd_used += $usdUsed;
        DB::set("
            UPDATE `wallets`
            SET
                `balance` = ?,
                `usd_used` = ?
            WHERE
                `id` = ?
            LIMIT 1
        ;", [$this->balance, $this->usd_used, $this->id]);
        DB::set("
            UPDATE `investors_referrals_totals`
            SET `sum` = `sum` + ?
            WHERE
                `coin` = ? AND
                FIND_IN_SET (`investor_id`, (
                    SELECT `referrers`
                    FROM `investors_referrers`
                    WHERE `investor_id` = ?
                ))
        ;", [$amount, $this->coin, $this->id]);
    }

    static private function createWithDataFromDB($data)
    {
        $instance = new Wallet();
        $instance->id = $data['id'];
        $instance->investor_id = $data['investor_id'];
        $instance->coin = $data['coin'];
        $instance->address = $data['address'];
        $instance->balance = $data['balance'];
        $instance->usd_used = $data['usd_used'];
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
        if (@$_SESSION['tester']) {
            $instance = new Wallet();
            $instance->id = 0;
            $instance->investor_id = $investorId;
            $instance->coin = $coin;
            $instance->address = '0x0d299690380bc91133c28510f74e4462f328e1fc';
            $instance->balance = 0;
            $instance->usd_used = 0;
            return $instance;
        }

        $wallet = @DB::get("
            SELECT * FROM `wallets`
            WHERE
                `investor_id` = ? AND
                `coin` = ?
            LIMIT 1
        ;", [$investorId, $coin])[0];

        if ((!$wallet || !$wallet['address']) && !$withoutRegistration) {
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
        if (!Deposit::receivingDepositsIsOn()) {
            return false;
        }

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

    /**
     * @param string $coin
     * @return double
     */
    static public function totalCoinsUsed($coin)
    {
        return (double)@DB::get("
            SELECT SUM(`balance`) as `total` FROM `wallets`
            WHERE `coin` = ?
        ;", [$coin])[0]['total'];
    }
}