<?php

namespace core\models;

use core\engine\Application;
use core\engine\DB;
use core\engine\Utility;

class EtherWallet
{
    public $id = 0;

    /**
     * @var Investor
     */
    public $investor = null;
    public $eth_address = '';
    public $datetime_create = 0;
    public $datetime_update = 0;
    public $eth = 0;
    public $cpt = 0;

    const SECS_TO_UPDATE_WALLET = 60 * 5;

    static public function db_init()
    {
        DB::query("
            CREATE TABLE IF NOT EXISTS `eth_queue_wallets` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `investor_id` int(10) UNSIGNED NOT NULL,
                `eth_address` varchar(50) DEFAULT '',
                `datetime_create` datetime(0) NOT NULL,
                `datetime_update` datetime(0) NOT NULL,
                `eth` double(20, 8) UNSIGNED DEFAULT '0',
                `cpt` double(20, 8) UNSIGNED DEFAULT '0',
                PRIMARY KEY (`id`)
            )
            DEFAULT CHARSET utf8
            DEFAULT COLLATE utf8_general_ci
        ;");
    }

    /**
     * @param array $data
     * @return EtherWallet
     */
    static private function constructFromDbData($data)
    {
        $instance = new EtherWallet();
        $instance->id = $data['id'];
        $instance->investor = Investor::getById($data['investor_id']);
        $instance->eth_address = $data['eth_address'];
        $instance->datetime_create = strtotime($data['datetime_create']);
        $instance->datetime_update = strtotime($data['datetime_update']);
        $instance->eth = $data['eth'];
        $instance->cpt = $data['cpt'];
        return $instance;
    }

    static public function create($investorId, $ethAddress, $ethBalance, $cptBalance)
    {
        DB::set("
            INSERT INTO `eth_queue_wallets`
            SET
                `investor_id` = ?,
                `eth_address` = ?,
                `datetime_create` = NOW(),
                `datetime_update` = NOW(),
                `eth` = ?,
                `cpt` = ?
            ;", [$investorId, $ethAddress, $ethBalance, $cptBalance]
        );
        return self::getFromDbByInvestorId($investorId);
    }

    /**
     * @param int $investorId
     * @return EtherWallet|null
     */
    static public function getByInvestorId($investorId)
    {
        $wallet = self::getFromDbByInvestorId($investorId);
        if (!is_null($wallet)) {
            if (time() - $wallet->datetime_update > self::SECS_TO_UPDATE_WALLET) {
                return EthQueue::getWallet($investorId);
            }
            return $wallet;
        }
        return EthQueue::getWallet($investorId);
    }

    /**
     * @param int $investorId
     * @return EtherWallet|null
     */
    static public function getFromDbByInvestorId($investorId)
    {
        $data = @DB::get("
            SELECT *
            FROM `eth_queue_wallets`
            WHERE
                `investor_id` = ?
            LIMIT 1
        ;", [$investorId])[0];

        if (!$data) {
            return null;
        }

        $instance = self::constructFromDbData($data);
        return $instance;
    }

    /**
     * @param double $eth
     * @param double $cpt
     */
    public function update($eth, $cpt)
    {
        $this->eth = (double)$eth;
        $this->cpt = (double)$cpt;
        DB::set("
            UPDATE `eth_queue_wallets`
            SET
                `datetime_update` = NOW(),
                `eth` = ?,
                `cpt` = ?
            WHERE `id` = ?
        ;", [$this->eth, $this->cpt, $this->id]);
    }

    public function resetUpdateEndDateTime()
    {
        $this->datetime_end = time();
        DB::set("
            UPDATE `eth_queue` SET
                `datetime_end` = 0
            WHERE `id` = ?
        ;", [$this->id]);
    }

    /**
     * @param double $ethValue
     * @param string $ethAddress
     * @return bool
     */
    public function sendEth($ethValue, $ethAddress)
    {
        if (!EthQueue::SENDETHWALLET_IS_ON) {
            return false;
        }

        if ($this->eth < $ethValue) {
            return false;
        }

        if (!Utility::validateEthAddress($ethAddress)) {
            return false;
        }

        $this->update($this->eth - $ethValue, $this->cpt);
        $data = [
            'eth' => $ethValue
        ];
        list($code) = EthQueue::sendEthWallet($this->investor->id, $data, $ethAddress, $ethValue);
        return $code >= 0;
    }

    /**
     * @param double $cptValue
     * @param string $ethAddress
     * @return bool
     */
    public function sendCpt($cptValue, $ethAddress)
    {
        if (!EthQueue::SENDCPTWALLET_IS_ON) {
            return false;
        }

        if ($this->cpt < $cptValue) {
            return false;
        }

        if (!Utility::validateEthAddress($ethAddress)) {
            return false;
        }

        $this->update($this->eth, $this->cpt - $cptValue);
        $data = [
            'cpt' => $cptValue
        ];
        list($code) = EthQueue::sendCptWallet($this->investor->id, $data, $ethAddress, $cptValue);
        return $code >= 0;
    }
}