<?php

namespace core\models;

use core\controllers\Bounty_controller;
use core\controllers\Investor_controller;
use core\engine\Application;
use core\engine\DB;
use core\engine\Utility;

class Investor
{
    public $id = 0;
    public $referrer_id = 0;
    public $referrer_code = '';
    public $joined_datetime = 0;
    public $email = '';
    public $firstname = '';
    public $lastname = '';
    public $eth_address = '';
    public $eth_withdrawn = 0;
    public $tokens_count = 0;
    public $eth_not_used_in_bounty = 0;
    public $eth_bounty = 0;
    public $phone = '';
    /**
     * @var null|Investor[]
     */
    public $referrals = null;
    /**
     * @var null|Investor[]
     */
    public $compressed_referrals = null;

    static private $storage = [];
    static private $investors_referrals_compressed = [];

    static public function db_init()
    {
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `referrer_id` int(10) UNSIGNED DEFAULT '0',
                `referrer_code` varchar(32) DEFAULT '',
                `joined_datetime` datetime(0) NOT NULL,
                `email` varchar(254) NOT NULL,
                `firstname` varchar(254) DEFAULT '',
                `lastname` varchar(254) DEFAULT '',
                `password_hash` varchar(254) NOT NULL,
                `eth_address` varchar(50) DEFAULT '',
                `eth_withdrawn` double(20, 8) DEFAULT '0',
                `tokens_count` double(20, 8) UNSIGNED DEFAULT '0',
                `eth_not_used_in_bounty` double(20, 8) UNSIGNED DEFAULT '0',
                `eth_bounty` double(20, 8) UNSIGNED DEFAULT '0',
                `eth_new_bounty` double(20, 8) UNSIGNED DEFAULT '0',
                `phone` varchar(254) DEFAULT '',
                PRIMARY KEY (`id`),
                INDEX `referrer_id_index`(`referrer_id`) USING HASH,
            )
            DEFAULT CHARSET utf8
            DEFAULT COLLATE utf8_general_ci
        ;");
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors_referrals_compressed` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `investor_id` int(10) UNSIGNED DEFAULT '0',
                `referral_id` int(10) UNSIGNED DEFAULT '0',
                PRIMARY KEY (`id`),
                INDEX `investor_id_index`(`investor_id`) USING HASH,
                INDEX `referral_id_index`(`referral_id`) USING HASH
            )
            DEFAULT CHARSET utf8
            DEFAULT COLLATE utf8_general_ci
        ;");
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors_to_previous_system` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `investor_id` int(10) UNSIGNED DEFAULT '0',
                `previoussystem_id` int(10) UNSIGNED DEFAULT '0',
                PRIMARY KEY (`id`),
                INDEX `investor_id_index`(`investor_id`) USING HASH,
                INDEX `previoussystem_id_index`(`previoussystem_id`) USING HASH
            )
            DEFAULT CHARSET utf8
            DEFAULT COLLATE utf8_general_ci
        ;");
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors_waiting_tokens` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `investor_id` int(10) UNSIGNED DEFAULT '0',
                PRIMARY KEY (`id`),
                INDEX `investor_id_index`(`investor_id`) USING HASH
            )
            DEFAULT CHARSET utf8
            DEFAULT COLLATE utf8_general_ci
        ;");
    }

    static private function createWithDataFromDB($data)
    {
        $instance = new Investor();
        $instance->id = $data['id'];
        $instance->referrer_id = $data['referrer_id'];
        $instance->referrer_code = $data['referrer_code'];
        $instance->joined_datetime = strtotime($data['joined_datetime']);
        $instance->email = $data['email'];
        $instance->firstname = $data['firstname'];
        $instance->lastname = $data['lastname'];
        $instance->eth_address = $data['eth_address'];
        $instance->eth_withdrawn = $data['eth_withdrawn'];
        $instance->tokens_count = $data['tokens_count'];
        $instance->eth_not_used_in_bounty = $data['eth_not_used_in_bounty'];
        $instance->eth_bounty = $data['eth_bounty'];
        $instance->phone = $data['phone'];
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
     * @return Investor|null
     */
    static public function getByEmail($email)
    {
        if (isset(Investor::$storage[$email])) {
            return Investor::$storage[$email];
        }
        $investorData = @DB::get("
            SELECT * FROM `investors`
            WHERE
                `email` = ?
            LIMIT 1
        ;", [$email])[0];

        if (!$investorData) {
            return null;
        }

        $instance = self::createWithDataFromDB($investorData);
        Investor::$storage[$email] = $instance;
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
     * @param string $firstname
     * @param string $lastname
     * @param string $eth_address
     * @param int $referrer_id
     * @param string $password_hash
     * @return int if > 0 -> success, else error
     */
    static public function registerUser($email, $firstname, $lastname, $eth_address, $referrer_id, $password_hash)
    {
        if (!Utility::validateEthAddress($eth_address)) {
            return -1;
        }

        if (Investor::isExistWithParams($email)) {
            return -2;
        }

        if ($referrer_id) {
            $existingReferrer = @DB::get("
                SELECT * FROM `investors`
                WHERE
                    `id` = ?
                LIMIT 1
            ;", [$referrer_id])[0];
            if (!$existingReferrer) {
                return -3;
            }
        }

        $referrer_code = Investor_controller::generateReferrerCode();

        DB::set("
            INSERT INTO `investors`
            SET
                `referrer_id` = ?, `referrer_code` = ?,
                `joined_datetime` = ?,
                `email` = ?, `firstname` = ?, `lastname` = ?,
                `password_hash` = ?, `eth_address` = ?, `eth_withdrawn` = ?,
                `tokens_count` = ?
            ", [
                $referrer_id, $referrer_code,
                DB::timetostr(time()),
                $email, $firstname, $lastname,
                $password_hash, $eth_address, 0,
                0
            ]
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
     * @param string $firstname
     * @param string $lastname
     */
    public function setFirstnameLastName($firstname, $lastname)
    {
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        DB::set("
            UPDATE `investors`
            SET
                `firstname` = ?,
                `lastname` = ?
            WHERE
                `id` = ?
            LIMIT 1
        ", [$firstname, $lastname, $this->id]);
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
     * @param double $addedTokensCount
     */
    public function addTokens($addedTokensCount)
    {
        if ($addedTokensCount <= 0) {
            return;
        }

        $oldCollapseState = self::isInvestorCollapseInCompress($this);

        $this->tokens_count += $addedTokensCount;
        DB::set("
            UPDATE `investors`
            SET
                `tokens_count` = ?
            WHERE
                `id` = ?
            LIMIT 1
        ;", [$this->tokens_count, $this->id]);

        // если инвестор ранее не проходил по баунти-системе (компрессировался), а сейчас проходит,
        // то следует выполнить заполнение таблицы
        if (!$oldCollapseState) {
            if (self::isInvestorCollapseInCompress($this)) {
                $this->fill_referralsCompressedTable();
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

    public function addEthBounty($ethBounty)
    {
        if ($ethBounty > $this->eth_bounty) {
            return false;
        }

        $this->eth_bounty += $ethBounty;
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
        if (!@ETH_BOUNTY_COLD_WALLET) {
            return false;
        }

        if (!Coin::getRate(Coin::reinvestToken())) {
            return false;
        }

        if (!$this->spentEthBounty($ethToReinvest)) {
            return false;
        }

        $usdToReinvest = $ethToReinvest * Coin::getRate(Coin::COMMON_COIN);
        $tokens = (double)($usdToReinvest / Coin::getRate(Coin::reinvestToken()));

        $sendResult = Bounty_controller::sendEth(ETH_BOUNTY_COLD_WALLET, $ethToReinvest);
        if (is_string($sendResult)) {
            $txid_s = $sendResult;
            Utility::log('sendEth1/' . Utility::microtime_float(), [
                'investor' => $this->id,
                'txid' => $txid_s,
                'time' => time()
            ]);
            // todo: add eth to eth_not_used_in_bounty
            $mintResult = Bounty_controller::mintTokens($this, $tokens, 'eth', $txid_s);
            if (is_string($mintResult)) {
                $txid_m = $mintResult;
                Utility::log('mint2/' . Utility::microtime_float(), [
                    'investor' => $this->id,
                    'txid' => $txid_m,
                    'time' => time()
                ]);
                $this->addTokens($tokens);
            } else {
                Utility::log('mint_reinvest_not_happened/' . Utility::microtime_float(), [
                    'investor' => $this->id,
                    'txid' => $txid_s,
                    'time' => time(),
                    'ethToReinvest' => $ethToReinvest
                ]);
            }
        } else {
            $this->addEthBounty($ethToReinvest);
        }

        return true;
    }

    /**
     * @param double $eth
     * @return bool
     */
    public function withdraw($eth)
    {
        if (!$this->spentEthBounty($eth)) {
            return false;
        }

        $sendResult = Bounty_controller::sendEth($this->eth_address, $eth);
        if (is_string($sendResult)) {
            $txid = $sendResult;
            Utility::log('sendEth2/' . Utility::microtime_float(), [
                'investor' => $this->id,
                'txid' => $txid,
                'time' => time()
            ]);
            $this->eth_withdrawn += $eth;
            DB::set("
                UPDATE `investors`
                SET
                    `eth_withdrawn` = ?
                WHERE
                    `id` = ?
                LIMIT 1
            ;", [$this->eth_withdrawn, $this->id]);
        } else {
            $this->addEthBounty($eth);
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
        if (!isset(self::$investors_referrals_compressed[$investor_id])) {
            $compressedReferrals_data = @DB::get("
                SELECT `referral_id` FROM `investors_referrals_compressed`
                WHERE
                    `investor_id` = ?
            ;", [$investor_id]);
            self::$investors_referrals_compressed[$investor_id] = [];
            foreach ($compressedReferrals_data as $compressedReferral_data) {
                self::$investors_referrals_compressed[$investor_id][] = $compressedReferral_data['referral_id'];
            }
        }

        $compressedReferrals = self::$investors_referrals_compressed[$investor_id];
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
    public function initReferrals($levels)
    {
        if ($levels < 1) {
            return;
        }
        if ((@$_SERVER['REMOTE_ADDR']) && Application::executedTime() > 0.9) { // todo: it's prevent large time execution, but return wrong data
            return;
        }
        if (is_null($this->referrals)) {
            $this->referrals = self::referrals($this);
            $this->compressed_referrals = $this->referrals; // todo: it's fake! maked for more speed
        }
        foreach ($this->referrals as &$referral) {
            $referral->initReferrals($levels - 1);
        }
    }

    /**
     * @param int $levels
     */
    public function initCompressedReferrals($levels)
    {
        self::initReferrals($levels); // todo: it's fake! maked for more speed
        return;

        if ($levels < 1) {
            return;
        }

        if (is_null($this->compressed_referrals)) {
            $this->compressed_referrals = [];
            if (!isset(self::$investors_referrals_compressed[$this->id])) {
                $subquery = "
                    SELECT
                        `investor_id`, `referral_id`, CAST('1' as UNSIGNED) as `level`
                    FROM
                        `investors_referrals_compressed`
                    WHERE
                        `investor_id` in ({$this->id})
                ";
                $query = $subquery;
                for ($i = 2; $i <= $levels; ++$i) {
                    $subquery = "
                        SELECT
                            `investor_id`, `referral_id`, CAST('$i' as UNSIGNED) as `level`
                        FROM
                            `investors_referrals_compressed`
                        WHERE
                            `investor_id` in (" . preg_replace(
                            "/`investor_id`, `referral_id`, CAST\('[0-9]+' as UNSIGNED\) as `level`/",
                            '`referral_id`',
                            $subquery
                        ) . ")
                    ";
                    $query = "$query UNION $subquery";
                }
                $refsData = DB::get($query);
                foreach ($refsData as $refData) {
                    if (!isset(self::$investors_referrals_compressed[$refData['investor_id']])) {
                        self::$investors_referrals_compressed[$refData['investor_id']] = [];
                    }
                    if ($refData['level'] !== $levels) {
                        if (!isset(self::$investors_referrals_compressed[$refData['referral_id']])) {
                            self::$investors_referrals_compressed[$refData['referral_id']] = [];
                        }
                    }
                    self::$investors_referrals_compressed[$refData['investor_id']][] = $refData['referral_id'];
                }
            }

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
            $investor->initCompressedReferrals($levels - 1);
        }
    }

    public function referralsCount($isFirst = true)
    {
        $count = 0;
        if ($isFirst) {
            $this->initReferrals(count(Bounty::program()));
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
            $this->initCompressedReferrals(count(Bounty::program()));
        }
        foreach ($this->compressed_referrals as &$referral) {
            ++$count;
            $count += $referral->compressedReferralsCount(false);
        }
        return $count;
    }

    static public function fill_referralsCompressedTable_forAll($callback = null)
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
            $investor->fill_referralsCompressedTable();
            if (is_callable($callback)) {
                call_user_func($callback, $i, count($investors_data));
            }
        }
    }

    public function fill_referralsCompressedTable()
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
                    JOIN ( SELECT @ri := ? ) `tmp`
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
            $usdUsed += $wallet->usd_used;
        }
        return $usdUsed;
    }

    /**
     * @return array
     */
    public function coinsUsed()
    {
        $wallets = Wallet::getByInvestorid($this->id);
        $ar = [];
        foreach ($wallets as $wallet) {
            $ar[$wallet->coin] = $wallet->balance;
        }
        return $ar;
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
        $prevSysInvestor_data = DB::get("SELECT * FROM `investors` WHERE `email` = ? LIMIT 1;", [$email]);

        if (count($prevSysInvestor_data) === 0) {
            return -1;
        }

        $hash = $prevSysInvestor_data[0]['password_hash'];

        $FIRST_HASH_ITER = 2000;
        $firstHash = hash_pbkdf2('sha512', $password, sha1($password), $FIRST_HASH_ITER, 64);
        $pieces = explode('$', $hash);
        if (count($pieces) < 4) {
            return -2;
        }
        list($header, $iter, $salt, $hash) = $pieces;
        if (!preg_match('#^pbkdf2_([a-z0-9A-Z]+)$#', $header, $m)) {
            return -3;
        }
        $algo = $m[1];
        $secondHash = base64_encode(hash_pbkdf2($algo, $firstHash, $salt, $iter, 32, true));

        if (!hash_equals($secondHash, $hash)) {
            return -4;
        }

        return $prevSysInvestor_data[0]['id'];
    }
}