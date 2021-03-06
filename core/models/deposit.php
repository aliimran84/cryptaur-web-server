<?php

namespace core\models;

use core\controllers\Bounty_controller;
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
    public $registered = false;
    public $used_in_minting = false;

    const MINIMAL_TOKENS_FOR_MINTING_KEY = 'minimal_tokens_for_minting';
    const MINIMAL_TOKENS_FOR_BOUNTY_KEY = 'minimal_tokens_for_bounty';

    const RECEIVING_DEPOSITS_IS_ON = 'receiving_deposits_is_on';
    const MINTING_IS_ON = 'minting_is_on';

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
                `eth` double(20, 8) DEFAULT '-1',
                `datetime` datetime(0) NOT NULL,
                `is_donation` tinyint(1) UNSIGNED DEFAULT '0',
                `registered` tinyint(1) UNSIGNED DEFAULT '0',
                `used_in_minting` tinyint(1) UNSIGNED DEFAULT '0',
                PRIMARY KEY (`id`),
                INDEX `investor_id_index`(`investor_id`)
            )
            DEFAULT CHARSET utf8
            DEFAULT COLLATE utf8_general_ci
        ;");
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
        $instance->registered = (bool)$data['registered'];
        $instance->used_in_minting = (bool)$data['used_in_minting'];
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

        $coinRate = Coin::getRate($coin);
        if (is_null($coinRate)) {
            return false;
        }
        $usd = $coinRate * $amount;

        $tokenRate = Coin::getRate(Coin::token());

        $depositTokens = $usd / $tokenRate;

        $isDonation = $depositTokens < self::minimalTokensForNotToBeDonation();

        $receivingDepositsIsOn = self::receivingDepositsIsOn();

        if (DB::get("
            SELECT COUNT(*) AS `c` FROM `deposits`
            WHERE
                `investor_id` = ? AND
                `coin` = ? AND
                `txid` = ? AND
                `vout` = ?
            LIMIT 1
        ", [$investorId, $coin, $txid, $vout])[0]['c'] > 0) {
            Utility::log('double_deposit/' . Utility::microtime_float(), [
                'investorId' => $investorId,
                'amount' => $amount,
                'coin' => $coin,
                'txid' => $txid,
                'vout' => $vout
            ]);
            return true;
        }

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
                `is_donation` = ?,
                `registered` = ?
        ;", [$investorId, $coin, $txid, $vout, $amount, $usd, $coinRate, DB::timetostr(time()), $isDonation, $receivingDepositsIsOn]);

        if ($isDonation) {
            return true;
        }

        if (!$receivingDepositsIsOn) {
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
     * @param int $id
     * @return Deposit
     */
    static public function getById($id)
    {
        $db_deposit = DB::get("
            SELECT *
            FROM `deposits`
            WHERE
                `id` = ?
        ;", [$id])[0];
        return self::constructFromDbData($db_deposit);
    }

    /**
     * @param double $usd
     * @param double $rate
     */
    public function setUsdAndRate($usd, $rate)
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

    /**
     * @param bool $registered
     */
    public function setRegistered($registered)
    {
        $this->registered = $registered;
        DB::set("
            UPDATE `deposits`
            SET
                `registered` = ?
            WHERE
                `id` = ?
            LIMIT 1
        ;", [$registered, $this->id]);
    }

    /**
     * @param bool $usedInMinting
     */
    public function setUsedInMinting($usedInMinting)
    {
        $this->used_in_minting = $usedInMinting;
        DB::set("
            UPDATE `deposits`
            SET
                `used_in_minting` = ?
            WHERE
                `id` = ?
            LIMIT 1
        ;", [$usedInMinting, $this->id]);
    }

    /**
     * @param int $investorId
     */
    static private function tryMintTokens($investorId)
    {
        if (!self::mintingIsOn()) {
            return;
        }

        $db_deposits = DB::get("
            SELECT *
            FROM `deposits`
            WHERE
                `investor_id` = ? AND
                `used_in_minting` = 0 AND
                `is_donation` = 0
        ;", [$investorId]);

        $receivingDepositsIsOn = self::receivingDepositsIsOn();
        $tokenRate = Coin::getRate(Coin::token());
        $tokensToMinting = 0;
        $depositsForMinting = [];
        foreach ($db_deposits as $db_deposit) {
            $deposit = self::constructFromDbData($db_deposit);

            // если депозит не был принят, а сейчас может быть принят, то принимаем
            if ($receivingDepositsIsOn && !$deposit->registered) {
                $deposit->setRegistered(true);
                $wallet = Wallet::getByInvestoridCoin($investorId, $deposit->coin);
                if ($wallet) {
                    $wallet->addToWallet($deposit->amount, $deposit->usd);
                }
            }

            if ($deposit->registered) {
                $depositsForMinting[] = $deposit;
                $tokenRate_currentDeposit = $tokenRate;
                if (strtolower($deposit->coin) === 'xem') {
                    $tokenRate_currentDeposit *= 0.8;
                }
                $tokensToMinting += $deposit->usd / $tokenRate_currentDeposit;
            }
        }

        // если суммарно по депозитам инвестора превысили минимальный порог, то генерируем токены
        if ($tokensToMinting >= self::minimalTokensForMinting()) {
            $investor = Investor::getById($investorId);
            if (strlen($investor->eth_address) > 33) {
                $minting_coin = '';
                $minting_txid = '';
                $depositsId = [];
                foreach ($depositsForMinting as $i => $deposit) {
                    $minting_coin = $deposit->coin;
                    $minting_txid = $deposit->txid;
                    $depositsId[] = $deposit->id;
                    $deposit->setUsedInMinting(true);
                }
                // если процесс чеканке был инициирован не одним платежом, а несколькими, то выполняем реальную чеканку только одной операцией
                $data = [
                    'tokens' => $tokensToMinting,
                    'depositsId' => $depositsId
                ];
                EthQueue::mintTokens(EthQueue::TYPE_MINT_DEPOSIT, $investor->id, $data, $investor->eth_address, $tokensToMinting, $minting_coin, $minting_txid);
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
     * @return bool
     */
    static public function receivingDepositsIsOn()
    {
        return Application::getValue(self::RECEIVING_DEPOSITS_IS_ON);
    }

    /**
     * @return bool
     */
    static public function mintingIsOn()
    {
        return Application::getValue(self::MINTING_IS_ON);
    }
}