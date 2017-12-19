<?php

namespace core\models;

use core\controllers\Bounty_controller;
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
    public $eth_bounty = 0;
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

    static private $storage = [];

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
                `eth_bounty` double(20, 8) UNSIGNED DEFAULT '0',
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
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors_previous_system_credentials` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `investor_id` int(10) UNSIGNED DEFAULT '0',
                `password_hash` varchar(254) NOT NULL,
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
        $instance->eth_bounty = $data['eth_bounty'];
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
        if (isset(Investor::$storage[$id])) {
            return Investor::$storage[$id];
        }
        $investorData = @DB::get("
            SELECT * FROM `investors`
            WHERE
                `id` = ?
            LIMIT 1
        ;", [$id])[0];

        if (!$investorData) {
            return null;
        }

        $instance = self::createWithDataFromDB($investorData);
        Investor::$storage[$id] = $instance;
        return $instance;
    }

    /**
     * @param string $email
     * @param string $password
     * @return bool|int
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
     * @return bool
     */
    static public function isExistWithParams($email)
    {
        return !!@DB::get("
            SELECT * FROM `investors`
            WHERE
                `email` = ?
            LIMIT 1
        ;", [$email])[0];
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

        if (Investor::isExistWithParams($email)) {
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
     * @param string $password (not hashed)
     */
    public function changePassword($password)
    {
        $password_hash = Investor_controller::hashPassword($password);
        DB::set("
            UPDATE `investors`
            SET
                `password_hash` = ?
            WHERE
                `id` = ?
            LIMIT 1
        ", [$password_hash, $this->id]);
    }

    /**
     * @param string $eth_address
     */
    public function setEthAddress($eth_address)
    {
        $this->eth_address = $eth_address;
        DB::set("
            UPDATE `investors`
            SET
                `eth_address` = ?
            WHERE
                `id` = ?
            LIMIT 1
        ", [$eth_address, $this->id]);
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
     * @param int $addedTokensCount
     */
    public function addTokens($addedTokensCount)
    {
        if ($addedTokensCount <= 0) {
            return;
        }

        $oldCollapseState = self::isInvestorCollapseInCompress($this);

        $this->tokens_count += $addedTokensCount;
        $this->tokens_not_used_in_bounty += $addedTokensCount;
        DB::set("
            UPDATE `investors`
            SET
                `tokens_count` = ?,
                `tokens_not_used_in_bounty` = ?
            WHERE
                `id` = ?
            LIMIT 1
        ;", [$this->tokens_count, $this->tokens_not_used_in_bounty, $this->id]);

        // если инвестор ранее не проходил по баунти-системе (компрессировался), а сейчас проходит,
        // то следует выполнить заполнение таблицы
        if (!$oldCollapseState) {
            if (self::isInvestorCollapseInCompress($this)) {
                $this->fill_referalsCompressedTable();
            }
        }
    }

    public function spentEthBounty($ethBounty)
    {
        if ($ethBounty < 0) {
            return false;
        }
        if ($ethBounty > $this->eth_bounty) {
            return false;
        }

        $this->eth_bounty -= $ethBounty;
        DB::set("
            UPDATE `investors`
            SET
                `eth_bounty` = ?
            WHERE
                `id` = ?
            LIMIT 1
        ;", [$this->eth_bounty, $this->id]);
        return true;
    }

    /**
     * @param double $ethToReinvest
     * @return bool
     */
    public function reinvestEth($ethToReinvest)
    {
        if ($ethToReinvest > $this->eth_bounty) {
            return false;
        }

        if (!@ETH_BOUNTY_COLD_WALLET) {
            return false;
        }

        $usdToReinvest = $ethToReinvest * Coin::getRate(Coin::COMMON_COIN);
        $tokens = floor($usdToReinvest / Coin::getRate(Coin::token()));

        $usd_spent = $tokens * Coin::getRate(Coin::token());
        $eth_spent = $usd_spent / Coin::getRate(Coin::COMMON_COIN);

        if (Bounty_controller::mintTokens($this, $tokens) > 0) {
            if (Bounty_controller::sendEth(ETH_BOUNTY_COLD_WALLET, $eth_spent) > 0) {
                $this->addTokens($tokens);
                $this->spentEthBounty($eth_spent);
            }
        }

        return true;
    }

    /**
     * @param double $eth
     * @return bool
     */
    public function withdraw($eth)
    {
        if ($eth > $this->eth_bounty) {
            return false;
        }

        if (Bounty_controller::sendEth($this->eth_address, $eth) > 0) {
            $this->eth_withdrawn += $eth;
            DB::set("
                UPDATE `investors`
                SET
                    `eth_withdrawn` = ?
                WHERE
                    `id` = ?
                LIMIT 1
            ;", [$this->eth_withdrawn, $this->id]);
            $this->spentEthBounty($eth);
        }

        return true;
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
        foreach ($this->referrals as &$referral) {
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

        if (is_null($this->compressed_referrals)) {
            $this->compressed_referrals = [];

            $referralsIdByLevel = self::referrals_compressed($this->id, 1);
            if (count($referralsIdByLevel) === 0) {
                return;
            }

            foreach ($referralsIdByLevel[0] as $id) {
                $investor = self::getById($id);
                $this->compressed_referrals[] = $investor;
            }
        }

        foreach ($this->compressed_referrals as &$investor) {
            $investor->initCompressedReferalls($levels - 1);
        }
    }

    public function referralsCount($isFirst = true)
    {
        $count = 0;
        if ($isFirst) {
            $this->initReferalls(count(Bounty::program()));
        }
        if (is_null($this->referrals) || count($this->referrals) === 0) {
            return 0;
        }
        foreach ($this->referrals as &$referral) {
            ++$count;
            $count += $referral->referralsCount(false);
        }
        return $count;
    }

    public function compressedReferralsCount($isFirst = true)
    {
        $count = 0;
        if ($isFirst) {
            $this->initCompressedReferalls(count(Bounty::program()));
        }
        foreach ($this->compressed_referrals as &$referral) {
            ++$count;
            $count += $referral->compressedReferralsCount(false);
        }
        return $count;
    }

    static public function fill_referalsCompressedTable_forAll($callback = null)
    {
        DB::set("DELETE FROM `investors_referrals_compressed`;");
        $investors_data = @DB::get("
            SELECT
                `id` 
            FROM
                `investors`
            WHERE
                `tokens_count` >= ?
        ;", [Deposit::minimalTokensForBounty()]);
        foreach ($investors_data as $i => $investor_data) {
            $investor = self::getById($investor_data['id']);
            $investor->fill_referalsCompressedTable();
            if (is_callable($callback)) {
                call_user_func($callback, $i, count($investors_data));
            }
        }
    }

    public function fill_referalsCompressedTable()
    {
        // это будет заполнятся только для тех, кто участвует в компрессии
        if (self::isInvestorCollapseInCompress($this)) {
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
        ;", [$minTokens, $this->id]);
        unset($referrers_data[0]);

        // получаем всех дочерних реферралов
        $referrals_list_string = implode(',', array_merge([$this->id], self::referrals_id_recursive($this->id)));
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
            ;", [$referrer_data['id'], $this->id]);
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

    /**
     * @param string $email
     * @param string $password
     * @return int: < 0 - error; > 0 - investor id
     */
    static public function investorId_previousSystemCredentials($email, $password)
    {
        $prevSysInvestor_data = DB::get("
            SELECT
                `investors`.`id`,
                `investors`.`email`,
                `investors_previous_system_credentials`.`password_hash` 
            FROM
                `investors`
                LEFT JOIN `investors_previous_system_credentials` ON `investors_previous_system_credentials`.`investor_id` = `investors`.`id` 
            WHERE
                `email` = ?
                LIMIT 1
        ;", [$email]);

        if (count($prevSysInvestor_data) === 0) {
            return -1;
        }

        $hash = $prevSysInvestor_data[0]['password_hash'];

        $FIRST_HASH_ITER = 2000;
        $firstHash = hash_pbkdf2('sha512', $password, sha1($password), $FIRST_HASH_ITER, 64);
        $pieces = explode('$', $hash);
        list($header, $iter, $salt, $hash) = $pieces;
        if (!preg_match('#^pbkdf2_([a-z0-9A-Z]+)$#', $header, $m)) {
            return -2;
        }
        $algo = $m[1];
        $secondHash = base64_encode(hash_pbkdf2($algo, $firstHash, $salt, $iter, 32, true));

        if (!hash_equals($secondHash, $hash)) {
            return -3;
        }

        return $prevSysInvestor_data[0]['id'];
    }

    /**
     * @param int $investorId
     */
    static public function clearInvestor_previousSystemCredentials($investorId)
    {
        DB::set("
            DELETE FROM `investors_previous_system_credentials`
            WHERE
                `investor_id` = ?
            LIMIT 1
        ;", [$investorId]);
    }
}