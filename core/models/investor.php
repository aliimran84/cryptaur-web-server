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
    /**
     * @var null|Investor[]
     */
    public $compressed_referrals = null;

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
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors_referrals_compressed` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `investor_id` int(10) UNSIGNED DEFAULT '0',
                `referral_id` int(10) UNSIGNED DEFAULT '0',
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
     * @return Investor[]
     */
    static public function referrersToRoot($investor_id)
    {
        $investorsData = @DB::get("
            SELECT
                * 
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
        return $investor->tokens_count < Deposit::minimalTokensForBounty();
    }

    /**
     * @param Investor $investor
     * @return Investor[]
     */
    static public function referrals(&$investor)
    {
        $allInvestorsIdWithReffererId = @DB::get("
            SELECT `id` FROM `investors`
            WHERE
                `referrer_id` = ?
        ;", [$investor->id]);
        $referrals = [];
        foreach ($allInvestorsIdWithReffererId as $data) {
            $referrals[$data['id']] = Investor::getById($data['id']);
        }
        return $referrals;
    }

    /**
     * @param int $investor_id
     * @param int $levels
     * @return array[] investors id by level
     */
    static public function referrals_compressed($investor_id, $levels)
    {
        if ($levels < 1) {
            return [];
        }
        $referralsByLevel = [];
        $compressedReferrals_data = @DB::get("
            SELECT `referral_id` FROM `investors_referrals_compressed`
            WHERE
                `investor_id` = ?
        ;", [$investor_id]);
        $compressedReferrals = [];
        foreach ($compressedReferrals_data as $compressedReferral_data) {
            $compressedReferrals[] = $compressedReferral_data['referral_id'];
        }
        if (count($compressedReferrals) === 0) {
            return [];
        }
        $referralsByLevel[] = $compressedReferrals;

        $refReferrals = [];
        foreach ($compressedReferrals as $referral_id) {
            $oneRefReferralsByLevel = self::referrals_compressed($referral_id, $levels - 1);
            foreach ($oneRefReferralsByLevel as $oneRefReferrals) {
                $refReferrals[] = $oneRefReferrals;
            }
        }
        if (count($refReferrals) !== 0) {
            $referralsByLevel[] = $refReferrals;
        }

        return $referralsByLevel;
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
            $this->referrals = self::referrals($this);
        }
        foreach ($this->referrals as $referral) {
            $referral->initReferalls($levels - 1);
        }
    }

    /**
     * @param int $levels
     */
    public function initCompressedReferalls($levels)
    {
        if ($levels < 1) {
            return;
        }

        $referralsIdByLevel = self::referrals_compressed($this->id, 1);
        if (count($referralsIdByLevel) === 0) {
            return;
        }
        $referralsId = $referralsIdByLevel[0];

        $this->compressed_referrals = [];
        foreach ($referralsId as $id) {
            $investor = self::getById($id);
            $investor->initCompressedReferalls($levels - 1);
            $this->compressed_referrals[] = $investor;
        }
    }

    static public function fill_referalsCompressedTable_forInverstor(&$investor)
    {
        // это будет заполнятся только для тех, кто участвует в компрессии
        if (self::isInvestorCollapseInCompress($investor)) {
            return;
        }

        $minTokens = Deposit::minimalTokensForBounty();
        // получаем массив реферреров для выбраного инвестора выше до следующего (вместе с собой),
        // кто уже участвует в баунти или до самого верхнего родительского
        $referrers_data = @DB::get("
            SELECT
                `id`,
                @current_referrer_id := `referrer_id` AS `referrer_id`,
                `tokens_count`,
                @bounty_accepts := ( @bounty_accepts + CAST(`tokens_count` >= ? AS UNSIGNED INTEGER)) AS `bounty_accepts` 
            FROM
                `investors`
                JOIN ( SELECT @current_referrer_id := ?, @bounty_accepts := 0 ) AS `tmp` 
            WHERE
                `id` = @current_referrer_id AND
                @bounty_accepts < 2
            ORDER BY
                `id` DESC
        ;", [$minTokens, $investor->id]);
        unset($referrers_data[0]);

        // получаем всех дочерних реферралов
        $referrals_list_string = implode(',', array_merge([$investor->id], self::referrals_id_recursive($investor->id)));
        foreach ($referrers_data as $referrer_data) {
            // подчистим для всех полученных реферреров
            // имеющиеся ссылки на compressed рефералов из наших дочерних реферралов
            DB::set("
                DELETE 
                FROM
                    `investors_referrals_compressed` 
                WHERE
                    `investor_id` = ? AND
                    `referral_id` IN (
                        ?
                    )
            ;", [$referrer_data['id'], $referrals_list_string]);

            // устанавливаем ссылку теперь уже на себя
            DB::set("
                INSERT INTO `investors_referrals_compressed` 
                SET `investor_id` = ?,
                    `referral_id` = ?
            ;", [$referrer_data['id'], $investor->id]);
        }
    }

    /**
     * @param int $investor_id
     * @return int[] [1,2,4,5] without source investor
     */
    static public function referrals_id_recursive($investor_id)
    {
        $referrals_id_data = DB::get("
            SELECT `id` FROM (
                SELECT
                    `id`,
                    `referrer_id`,
                    @ri := concat(@ri, ',', `id`) AS `referrers_list`
                FROM
                    `investors`
                    JOIN ( SELECT @ri := ? ) `tmp `
                WHERE
                    find_in_set(`referrer_id`, @ri) > 0
                ORDER BY
                    `id` ASC
                ) as `referrals`
        ", [$investor_id]);
        $referrals_id = [];
        foreach ($referrals_id_data as $referral_id_data) {
            $referrals_id[] = $referral_id_data['id'];
        }
        return $referrals_id;
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