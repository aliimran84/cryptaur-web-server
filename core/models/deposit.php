<?php

namespace core\models;

use core\engine\DB;
use core\engine\Utility;

class Deposit
{
    public $id = 0;
    public $coin = '';
    public $txid = '';
    public $vout = 0;
    public $amount = 0;
    public $usd = 0;
    public $rate = 0;
    public $datetime = 0;
    public $investor_id = 0;
    public $used_in_minting = 0;
    public $used_in_bounty = 0;

    const MINIMAL_TOKENS_FOR_MINTING_KEY = 'minimal_tokens_for_minting';
    const MINIMAL_TOKENS_FOR_BOUNTY_KEY = 'minimal_tokens_for_bounty';

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
                `usd` double(20, 8) DEFAULT '-1',
                `rate` double(20, 8) DEFAULT '-1',
                `datetime` datetime(0) NOT NULL,
                `used_in_minting` tinyint(1) UNSIGNED DEFAULT '0',
                `used_in_bounty` tinyint(1) UNSIGNED DEFAULT '0',
                PRIMARY KEY (`id`)
            );
        ");
    }

    /**
     * @param array $data
     * @return Deposit
     */
    static public function constructFromDbData($data)
    {
        $instance = new Deposit();
        $instance->id = $data['id'];
        $instance->investor_id = $data['investor_id'];
        $instance->coin = $data['coin'];
        $instance->txid = $data['txid'];
        $instance->vout = $data['vout'];
        $instance->amount = $data['amount'];
        $instance->usd = $data['usd'];
        $instance->rate = $data['rate'];
        $instance->datetime = strtotime($data['datetime']);
        $instance->used_in_minting = (bool)$data['used_in_minting'];
        $instance->used_in_bounty = (bool)$data['used_in_bounty'];
        return $instance;
    }

    /**
     * @param double $amount
     * @param string $coin
     * @param int $conf
     * @param string $txid
     * @param int $vout
     * @param int $investorId
     * @return bool
     */
    static public function receiveDeposit($amount, $coin, $conf, $txid, $vout, $investorId)
    {
        Utility::logOriginalRequest('paymentServerDeposit/' . time());
        $upperCoin = strtoupper($coin);
        if ($conf < Coin::MIN_CONF[$upperCoin]) {
            return false;
        }

        $investor = Investor::getById($investorId);
        if (!$investor) {
            return true;
        }

        $rate = Coin::getRate($coin);
        $usd = $rate * $amount;
        DB::set("
        INSERT INTO `deposits`
        SET
            `investor_id` = ?,
            `coin` = ?,
            `txid` = ?,
            `vout` = ?,
            `amount` = ?,
            `usd` = ?,
            `rate` = ?,
            `datetime` = ?
        ;", [$investorId, $coin, $txid, $vout, $amount, $usd, $rate, DB::timetostr(time())]);

        $wallet = Wallet::getByInvestoridCoin($investorId, $coin);
        if (!$wallet) {
            return true;
        }
        $wallet->addToWallet($amount, $usd);

        return true;
    }

    /**
     * @param int $investorId
     * @return Deposit[]
     */
    static public function investorDeposits($investorId)
    {
        $deposits = [];
        $db_deposits = DB::get("SELECT * FROM `deposits` WHERE `investor_id` = ?", [$investorId]);
        foreach ($db_deposits as $db_deposit) {
            $deposit = self::constructFromDbData($db_deposit);
            $deposits[$deposit->id] = $deposit;
        }

        return $deposits;
    }
}