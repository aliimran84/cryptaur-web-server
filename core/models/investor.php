<?php

namespace core\models;

use core\controllers\Investor_controller;
use core\engine\DB;
use core\engine\Utility;

class Investor
{
    public $id = 0;
    public $referrer_id = 0;
    public $referrer_code = '';
    public $joined_datetime = 0;
    public $email = '';
    public $tokens_count = 0;
    public $tokens_not_used_in_bounty = 0;
    public $eth_address = '';
    public $eth_withdrawn = 0;
    /**
     * @var null|Investor[]
     */
    public $referrals = null;

    static public function db_init()
    {
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `referrer_id` int(10) UNSIGNED DEFAULT '0',
                `referrer_code` varchar(32) DEFAULT '',
                `joined_datetime` datetime(0) NOT NULL,
                `email` varchar(254) NOT NULL,
                `password_hash` varchar(254) NOT NULL,
                `eth_address` varchar(50) NOT NULL,
                `eth_withdrawn` double(20, 8) DEFAULT '0',
                `tokens_count` bigint(20) UNSIGNED DEFAULT '0',
                `tokens_not_used_in_bounty` bigint(20) UNSIGNED DEFAULT '0',
                `phone` varchar(254) DEFAULT '',
                PRIMARY KEY (`id`)
            );
        ");
    }

    static private function createWithDataFromDB($data)
    {
        $instance = new Investor();
        $instance->id = $data['id'];
        $instance->referrer_id = $data['referrer_id'];
        $instance->referrer_code = $data['referrer_code'];
        $instance->joined_datetime = strtotime($data['joined_datetime']);
        $instance->email = $data['email'];
        $instance->tokens_count = $data['tokens_count'];
        $instance->tokens_not_used_in_bounty = $data['tokens_not_used_in_bounty'];
        $instance->eth_address = $data['eth_address'];
        $instance->eth_withdrawn = $data['eth_withdrawn'];
        return $instance;
    }

    /**
     * @param int $id
     * @return Investor|null
     */
    static public function getById($id)
    {
        $investorData = @DB::get("
            SELECT * FROM `investors`
            WHERE
                `id` = ?
            LIMIT 1
        ;", [$id])[0];

        if (!$investorData) {
            return null;
        }

        return self::createWithDataFromDB($investorData);
    }

    /**
     * @param string $email
     * @param string $password
     * @return bool
     */
    static public function getInvestorIdByEmailPassword($email, $password)
    {
        $investor = DB::get("
            SELECT `id` FROM `investors`
            WHERE
                `email` = ? AND
                `password_hash` = ?
            LIMIT 1
        ;", [$email, Investor_controller::hashPassword($password)]);
        if (!$investor) {
            return false;
        }
        return $investor[0]['id'];
    }

    /**
     * @param string $code
     * @return false|number
     */
    static public function getReferrerIdByCode($code)
    {
        $investorId = @DB::get("
            SELECT `id` FROM `investors`
            WHERE
                `referrer_code` = ?
            LIMIT 1
        ;", [$code])[0]['id'];
        if (!$investorId) {
            return false;
        }
        return (int)$investorId;
    }

    /**
     * @param string $email
     * @param string $eth_address
     * @return bool
     */
    static public function isExistWithParams($email, $eth_address)
    {
        return !!@DB::get("
            SELECT * FROM `investors`
            WHERE
                `email` = ? OR
                `eth_address` = ?
            LIMIT 1
        ;", [$email, $eth_address])[0];
    }

    /**
     * @param string $email
     * @param string $eth_address
     * @param int $referrer_id
     * @param string $password_hash
     * @return false|int
     */
    static public function registerUser($email, $eth_address, $referrer_id, $password_hash)
    {
        if (!Utility::validateEthAddress($eth_address)) {
            return false;
        }

        if (Investor::isExistWithParams($email, $eth_address)) {
            return false;
        }

        if ($referrer_id) {
            $existingReferrer = @DB::get("
                SELECT * FROM `investors`
                WHERE
                    `id` = ?
                LIMIT 1
            ;", [$referrer_id])[0];
            if (!$existingReferrer) {
                return false;
            }
        }

        $referrer_code = Investor_controller::generateReferrerCode();

        DB::set("
            INSERT INTO `investors`
            SET
                `referrer_id` = ?,
                `referrer_code` = ?,
                `joined_datetime` = ?,
                `email` = ?,
                `password_hash` = ?,
                `eth_address` = ?,
                `eth_withdrawn` = ?,
                `tokens_count` = ?
            ", [$referrer_id, $referrer_code, DB::timetostr(time()), $email, $password_hash, $eth_address, 0, 0]
        );

        return DB::lastInsertId();
    }

    /**
     * return array of investors from target investor to
     * @param int $investor_id
     * @return array
     */
    static public function referrersToRoot($investor_id)
    {
        $investorsData = @DB::get("
            SELECT
                id, email, referrer_id 
            FROM
                ( SELECT * FROM investors ORDER BY id DESC ) AS investors_sorted,
                ( SELECT @temp_referrer_id := ? ) AS initialisation 
            WHERE
                id = @temp_referrer_id 
                AND ( referrer_id = 0 OR @temp_referrer_id := referrer_id )
        ;", [$investor_id]);
        $investors = [];
        foreach ($investorsData as $investorData) {
            $investors[] = self::createWithDataFromDB($investorData);
        }
        return $investors;
    }

    /**
     * @param Investor $investor
     * @return bool
     */
    static private function isInvestorCollapseInCompress(&$investor)
    {
        return (bool)($investor->tokens_count >= Deposit::minimalTokensForBounty());
    }

    /**
     * @param Investor $investor
     * @param bool $withCompressing
     * @return Investor[]
     */
    static public function summonedBy(&$investor, $withCompressing = false)
    {
        $allInvestorsIdWithReffererId = @DB::get("
            SELECT `id` FROM `investors`
            WHERE
                `referrer_id` = ?
        ;", [$investor->id]);
        $summonedInvestors = [];
        foreach ($allInvestorsIdWithReffererId as $data) {
            $summonedInvestor = Investor::getById($data['id']);
            if (!$withCompressing || self::isInvestorCollapseInCompress($summonedInvestor)) {
                $summonedInvestors[$data['id']] = $summonedInvestor;
            } else {
                foreach (self::summonedBy($summonedInvestor, true) as $compressedInvestor) {
                    $summonedInvestors[$compressedInvestor->id] = $compressedInvestor;
                }
            }
        }
        return $summonedInvestors;
    }

    /**
     * @param int $levels
     */
    public function initReferalls($levels)
    {
        if ($levels < 1) {
            return;
        }
        if (is_null($this->referrals)) {
            $this->referrals = self::summonedBy($this);
        }
        foreach ($this->referrals as $referral) {
            $referral->initReferalls($levels - 1);
        }
    }

    /**
     * @return double
     */
    public function usdUsed()
    {
        $usdUsed = 0;
        $wallets = Wallet::getByInvestorid($this->id);
        foreach ($wallets as $wallet) {
            $usdUsed += $wallet->usdUsed;
        }
        return $usdUsed;
    }

    /**
     * @return int
     */
    static public function totalTokens()
    {
        $tokens = (int)@DB::get("
            SELECT SUM(`tokens_count`) as `total_tokens` FROM `investors`
        ;")[0]['total_tokens'];
        return $tokens;
    }

    /**
     * @return int
     */
    static public function totalInvestors()
    {
        $totalInvestors = (int)@DB::get("
            SELECT COUNT(`id`) as `total_investors` FROM `investors`
        ;")[0]['total_investors'];
        return $totalInvestors;
    }
}