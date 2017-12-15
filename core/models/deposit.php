<?php

namespace core\models;

use core\engine\Application;
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
    public $is_donation = false;
    public $used_in_minting = false;
    public $used_in_bounty = false;

    const MINIMAL_TOKENS_FOR_MINTING_KEY = 'minimal_tokens_for_minting';
    const MINIMAL_TOKENS_FOR_BOUNTY_KEY = 'minimal_tokens_for_bounty';

    const RECEIVING_DEPOSITS_IS_ON = 'receiving_deposits_is_on';

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
                `is_donation` tinyint(1) UNSIGNED DEFAULT '0',
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
        $instance->is_donation = (bool)$data['is_donation'];
        $instance->used_in_minting = (bool)$data['used_in_minting'];
        $instance->used_in_bounty = (bool)$data['used_in_bounty'];
        return $instance;
    }

    /**
     * @param double $amount
     * @param string $coin
     * @param string $txid
     * @param int $vout
     * @param int $investorId
     * @return bool
     */
    static public function receiveDeposit($amount, $coin, $txid, $vout, $investorId)
    {
        $investor = Investor::getById($investorId);
        if (!$investor) {
            return true;
        }

        // todo: is it not a event time? set USD = 0, RATE = 0 and must not to do minting and must not to put into wallet!
        $coinRate = Coin::getRate($coin);
        if (is_null($coinRate)) {
            return false;
        }
        $usd = $coinRate * $amount;

        $tokenRate = Coin::getRate(Coin::token());

        $depositTokens = $usd / $tokenRate;

        $isDonation = $depositTokens < self::minimalTokensForNotToBeDonation();

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
                `datetime` = ?,
                `is_donation` = ?
        ;", [$investorId, $coin, $txid, $vout, $amount, $usd, $coinRate, DB::timetostr(time()), $isDonation]);

        if ($isDonation) {
            return true;
        }

        $wallet = Wallet::getByInvestoridCoin($investorId, $coin);
        if (!$wallet) {
            return true;
        }
        $wallet->addToWallet($amount, $usd);

        self::tryMintTokens($investorId);

        return true;
    }

    /**
     * @param double $usd
     * @param double $rate
     */
    private function setUsdAndRate($usd, $rate)
    {
        $this->usd = $usd;
        $this->rate = $rate;
        DB::set("
            UPDATE `deposits`
            SET
                `usd` = ?,
                `rate` = ?
            WHERE
                `id` = ?
            LIMIT 1
        ;", [$usd, $rate, $this->id]);
    }

    static private function tryMintTokens($investorId)
    {
        // todo: is it not a event time? not to do minting!

        $db_deposits = DB::get("
            SELECT *
            FROM `deposits`
            WHERE
                `investor_id` = ? AND
                `used_in_minting` = 0
        ;", [$investorId]);

        $tokenRate = Coin::getRate(Coin::token());
        $tokensToMinting = 0;
        $depositsForMintig = [];
        foreach ($db_deposits as $db_deposit) {
            $deposit = self::constructFromDbData($db_deposit);

            // если депозит был принят не в эвент, то устанавливаем rate и usd только сейчас
            if (!$deposit->rate && !$deposit->usd) {
                $coinRate = Coin::getRate($deposit->coin);
                $usd = $coinRate * $deposit->amount;
                $deposit->setUsdAndRate($usd, $coinRate);
            }

            $depositsForMintig[] = $deposit;
            $tokensToMinting += $deposit->usd / $tokenRate;
        }

        if ($tokensToMinting >= self::minimalTokensForMinting()) {
            foreach ($depositsForMintig as $i => $deposit) {
                // если процесс чеканке был инициирован не одним платежом, а несколькими, то выполняем реальную чеканку только одной операцией
                $realTokensMinting = 0;
                if ($i === count($depositsForMintig)) {
                    $realTokensMinting = $tokensToMinting;
                }
//                todo: request real mint
//                $deposit->investor_id;
//                $deposit->coin;
//                $deposit->txid;
//                $realTokensMinting
            }
        }
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

    /**
     * @return int
     */
    static public function minimalTokensForMinting()
    {
        return Application::getValue(self::MINIMAL_TOKENS_FOR_MINTING_KEY);
    }

    /**
     * @return int
     */
    static public function minimalTokensForNotToBeDonation()
    {
        return Application::getValue(self::MINIMAL_TOKENS_FOR_MINTING_KEY) / 10;
    }

    /**
     * @return int
     */
    static public function minimalTokensForBounty()
    {
        return Application::getValue(self::MINIMAL_TOKENS_FOR_BOUNTY_KEY);
    }

    /**
     * @return int
     */
    static public function receivingDepositsIsOn()
    {
        return Application::getValue(self::RECEIVING_DEPOSITS_IS_ON);
    }

}