<?php

namespace core\models;

use core\controllers\Bounty_controller;
use core\controllers\Investor_controller;
use core\engine\Application;
use core\engine\DB;
use core\engine\Utility;

class Investor
{
    const ALLREFERRALS_CACHE_PATH_TO_DIR = PATH_TO_TMP_DIR . '/investorsReferrals';
    const ALLREFERRALS_CACHE_TIMEOUT = 3600; // sec

    const REGISTERING_IS_LOCKED_KEY = 'REGISTERING_IS_LOCKED_KEY';

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
    public $referrals_totals = [];
    public $eth_not_used_in_bounty = 0;
    public $eth_bounty = 0;
    public $phone = '';
    public $preferred_2fa = '';
    /**
     * @var Investor[]
     */
    public $referrals = [];
    /**
     * @var int[]
     */
    private $referralsId = [];
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
                INDEX `referrer_id_index`(`referrer_id`) USING HASH
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
            CREATE TABLE IF NOT EXISTS `investors_ethaddresses` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `investor_id` int(10) UNSIGNED DEFAULT '0',
                `eth_address` varchar(50) DEFAULT '',
                `datetime` datetime(0) NOT NULL,
                PRIMARY KEY (`id`),
                INDEX `investor_id_index`(`investor_id`) USING HASH
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
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors_referrers` (
                `investor_id` int(10) UNSIGNED NOT NULL,
                `referrers` varchar(10000) DEFAULT '',
                PRIMARY KEY (`investor_id`)
            )
            DEFAULT CHARSET utf8
            DEFAULT COLLATE utf8_general_ci
        ;");
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors_referrals` (
                `investor_id` int(10) UNSIGNED NOT NULL,
                `referrals` mediumtext,
                PRIMARY KEY (`investor_id`)
            )
            DEFAULT CHARSET latin1
            DEFAULT COLLATE latin1_general_ci
        ;");
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors_referrals_totals` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `investor_id` int(10) UNSIGNED NOT NULL,
                `coin` varchar(32) NOT NULL,
                `sum` double(20, 8) UNSIGNED NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                INDEX `investor_id_index`(`investor_id`) USING HASH
            )
            DEFAULT CHARSET utf8
            DEFAULT COLLATE utf8_general_ci
        ;");
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors_stage_0_bounty` (
                `investor_id` int(10) UNSIGNED NOT NULL,
                `eth_bounty` double(20, 8) UNSIGNED NULL DEFAULT 0,
                PRIMARY KEY (`investor_id`)
            )
            DEFAULT CHARSET utf8
            DEFAULT COLLATE utf8_general_ci
        ;");
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors_2fa_choice` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `investor_id` int(10) UNSIGNED NOT NULL,
                `choice` varchar(32) DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE `investor_id_index`(`investor_id`) USING HASH
            )
            DEFAULT CHARSET utf8
            DEFAULT COLLATE utf8_general_ci
        ;");
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors_ethtools_id` (
                `investor_id` int(10) UNSIGNED NOT NULL,
                `ethtool_id` int(10) UNSIGNED NOT NULL,
                PRIMARY KEY (`investor_id`)
            )
        ;");
        if (!@DB::get("
            SELECT count(*) as `count` FROM `investors_ethtools_id`
        ")[0]['count']) {
            DB::query("
                INSERT INTO `investors_ethtools_id` ( `investor_id`, `ethtool_id` )
                SELECT `id`, `id`
                FROM `investors`
            ;");
        }
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors_compressed_referrers` (
                `investor_id` int(10) UNSIGNED NOT NULL,
                `compressed_referrer` int(10) UNSIGNED NOT NULL,
                PRIMARY KEY (`investor_id`),
                INDEX `compressed_referrer_index`(`compressed_referrer`)
            )
        ;");
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors_stage_1_deposited_reinvested` (
                `investor_id` int(10) UNSIGNED NOT NULL,
                `eth_deposited_reinvested` double(20, 8) UNSIGNED NULL DEFAULT 0,
                PRIMARY KEY (`investor_id`)
            )
        ;");
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors_stage_1_bounty` (
                `investor_id` int(10) UNSIGNED NOT NULL,
                `eth_bounty` double(20, 8) UNSIGNED NULL DEFAULT 0,
                PRIMARY KEY (`investor_id`)
            )
        ;");
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors_stage_2_reinvested` (
                `investor_id` int(10) UNSIGNED NOT NULL,
                `eth_reinvested` double(20, 8) UNSIGNED NULL DEFAULT 0,
                PRIMARY KEY (`investor_id`)
            )
        ;");
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors_stage_2_deposited` (
                `investor_id` int(10) UNSIGNED NOT NULL,
                `eth_deposited` double(20, 8) UNSIGNED NULL DEFAULT 0,
                PRIMARY KEY (`investor_id`)
            )
        ;");
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors_compressed_level_eth` (
                `investor_id` int(10) UNSIGNED NOT NULL,
                `eth_level` double(20, 8) UNSIGNED NULL DEFAULT 0,
                PRIMARY KEY (`investor_id`)
            )
        ;");
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors_stage_2_bounty` (
                `investor_id` int(10) UNSIGNED NOT NULL,
                `eth_bounty` double(20, 8) UNSIGNED NULL DEFAULT 0,
                PRIMARY KEY (`investor_id`)
            )
        ;");
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors_stage_2_tokens` (
                `investor_id` int(10) UNSIGNED NOT NULL,
                `tokens` double(20, 8) UNSIGNED NULL DEFAULT 0,
                PRIMARY KEY (`investor_id`)
            )
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
        $instance->referrals_totals = [
            Coin::token() => 0
        ];
        $instance->preferred_2fa = $data['preferred_2fa'];
        $referrals_total_all = @json_decode($data['referrals_totals'], true);
        if (is_array($referrals_total_all)) {
            foreach ($referrals_total_all as $referrals_total) {
                $instance->referrals_totals[$referrals_total['coin']] = $referrals_total['sum'];
            }
        }
        if (@$_SESSION['tester']) {
            $instance->eth_bounty = 3.19;
            $instance->tokens_count = 28130.948;
            $instance->eth_withdrawn = 2.661;
        }
        return $instance;
    }

    /**
     * @param int $id
     * @return Investor|null
     */
    static public function getById($id)
    {
        $id_arr = [$id];
        self::getByIdArr($id_arr);
        if (!isset(Investor::$storage[$id])) {
            return null;
        }
        return Investor::$storage[$id];
    }

    static private function createInvestorForGetByIdArr(&$investorData)
    {
        $id = $investorData['id'];

        if (isset(Investor::$storage[$id])) {
            return;
        }

        $instance = self::createWithDataFromDB($investorData);
        Investor::$storage[$id] = $instance;

        $referralsId = explode(',', $investorData['referrals_id']);
        foreach ($referralsId as $referralId_str) {
            $referralId = (int)$referralId_str;
            $instance->referralsId[] = $referralId;
            if (isset(Investor::$storage[$referralId])) {
                $instance->referrals[$referralId] = Investor::$storage[$referralId];
            }
        }

        if (isset(Investor::$storage[$instance->referrer_id])) {
            Investor::$storage[$instance->referrer_id]->referrals[$id] = $instance;
        }
    }

    /**
     * @param int[] $idArr
     * @return mixed
     */
    static public function getByIdArr(&$idArr)
    {
        $idArrToInit = [];
        foreach ($idArr as $id) {
            if (!isset(Investor::$storage[$id])) {
                $idArrToInit[] = $id;
            }
        }

        $strToInit = implode($idArrToInit, ',');
        if (!$strToInit) {
            return [];
        }
        $investorsData = @DB::get("
            SELECT
                `investors`.*,
                (
                    SELECT CONCAT(
                        '[',
                        GROUP_CONCAT(JSON_OBJECT('coin', coin, 'sum', sum)),
                        ']'
                    ) FROM `investors_referrals_totals` WHERE `investor_id` = `investors`.`id`
                ) as `referrals_totals`,
                `investors_2fa_choice`.`choice` as `preferred_2fa`,
                (
                    SELECT GROUP_CONCAT(`id` SEPARATOR ',')
                    FROM `investors` as `inner_investors`
                    WHERE `referrer_id` = `investors`.`id`
                ) as referrals_id
            FROM `investors`
            LEFT JOIN `investors_2fa_choice` ON `investors`.`id` = `investors_2fa_choice`.`investor_id`
            WHERE
                `investors`.`id` in ($strToInit)
        ;");

        foreach ($investorsData as &$investorData) {
            self::createInvestorForGetByIdArr($investorData);
        }

        return $investorsData;
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
            SELECT
                `investors`.*,
                (
                    SELECT CONCAT(
                        '[',
                        GROUP_CONCAT(JSON_OBJECT('coin', coin, 'sum', sum)),
                        ']'
                    ) FROM `investors_referrals_totals` WHERE `investor_id` = `investors`.`id`
                ) as `referrals_totals`,
                `investors_2fa_choice`.`choice` as `preferred_2fa`
            FROM `investors`
            LEFT JOIN `investors_2fa_choice` ON `investors`.`id` = `investors_2fa_choice`.`investor_id`
            WHERE
                `investors`.`email` = ?
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

    static public function getInvestorIdByEthtoolsId($ethtoolsId)
    {
        $id = @DB::get("
            SELECT `investor_id`
            FROM `investors_ethtools_id` 
            WHERE `ethtool_id` = ?
        ;", [$ethtoolsId])[0]['investor_id'];
        return $id;
    }

    static public function getEthtoolsIdByInvestorId($investorId)
    {
        $id = @DB::get("
            SELECT `ethtool_id`
            FROM `investors_ethtools_id` 
            WHERE `investor_id` = ?
        ;", [$investorId])[0]['ethtool_id'];
        return $id;
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
     * @param int $referrer_id
     * @param string $password_hash
     * @return int if > 0 -> success, else error
     */
    static public function registerUser($email, $firstname, $lastname, $referrer_id, $password_hash, $phone)
    {
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

        if (Application::getValue(self::REGISTERING_IS_LOCKED_KEY)) {
            return -4;
        }

        $referrer_code = Investor_controller::generateReferrerCode();

        DB::set("
            INSERT INTO `investors`
            SET
                `referrer_id` = ?, `referrer_code` = ?,
                `joined_datetime` = ?,
                `email` = ?, `firstname` = ?, `lastname` = ?, `phone` = ?,
                `password_hash` = ?, `eth_address` = ?, `eth_withdrawn` = ?,
                `tokens_count` = ?
            ;", [
                $referrer_id, $referrer_code,
                DB::timetostr(time()),
                $email, $firstname, $lastname, $phone,
                $password_hash, '', 0,
                0
            ]
        );

        $investorId = DB::lastInsertId();

        DB::set("
            INSERT INTO `investors_ethtools_id`
            SET
                `investor_id` = ?,
                `ethtool_id` = ?
            ;", [
                $investorId, $investorId
            ]
        );
        DB::set("
            INSERT INTO `investors_referrals_totals`
            SET
                `investor_id` = ?,
                `coin` = ?
            ;", [
                $investorId, Coin::token()
            ]
        );
        foreach (Coin::coins() as $coin) {
            DB::set("
                INSERT INTO `investors_referrals_totals`
                SET
                    `investor_id` = ?,
                    `coin` = ?
                ;", [
                    $investorId, $coin
                ]
            );
        }

        DB::set("
            INSERT INTO `investors_referrers` (`investor_id`, `referrers`)
            VALUES
            (
                ?,
                (
                    SELECT CAST(referrers as CHAR(10000)) as referrers FROM (
                        SELECT referrer_id, @g := IF(@g = '', referrer_id, concat(@g, ',', referrer_id)) as referrers
                        FROM
                                `investors`,
                                ( SELECT @g := '' ) AS `tmp`,
                                ( SELECT @pv :=  ? ) AS `initialisation`
                        WHERE `id` = @pv AND @pv := `referrer_id`
                        ORDER BY `id` DESC
                        ) AS tmp
                        ORDER BY referrer_id ASC
                        LIMIT 1
                )
            )
            ;", [
                $investorId, $investorId
            ]
        );
        DB::set("
            INSERT INTO `investors_referrals`
            SET `investor_id` = ?,
                `referrals` = ''
            ;", [
                $investorId
            ]
        );
        DB::set("
            UPDATE `investors_referrals`
            SET `referrals` = IF(
                `referrals` = '' || `referrals` is null, ?, concat(`referrals`, ',', ?)
            )
            WHERE FIND_IN_SET(`investor_id`, (
                SELECT `referrers`
                FROM `investors_referrers`
                WHERE `investor_id` = ?
            ))
            ;", [
                $investorId, $investorId, $investorId
            ]
        );

        return $investorId;
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
     * @param int $investorId
     * @param string $eth_address
     */
    static public function setEthAddress_static($investorId, $eth_address)
    {
        $eth_address = strtolower($eth_address);
        $investor = self::getById($investorId);
        if ($investor->eth_address == $eth_address) {
            return;
        }
        $investor->eth_address = $eth_address;
        DB::set("
            UPDATE `investors`
            SET
                `eth_address` = ?
            WHERE
                `id` = ?
            LIMIT 1
        ", [$eth_address, $investorId]);
        DB::set("
            INSERT INTO `investors_ethaddresses`
            SET
                `investor_id` = ?,
                `eth_address` = ?,
                `datetime` = NOW()
            ", [$investorId, $eth_address]
        );
    }

    /**
     * @param string $eth_address
     */
    public function setEthAddress($eth_address)
    {
        self::setEthAddress_static($this->id, $eth_address);
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
     * @param string $method
     */
    public function set2faMethod($method)
    {
        $this->preferred_2fa = $method;
        DB::set("
            INSERT INTO `investors_2fa_choice` (`investor_id`, `choice`) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE `choice` = ?
        ", [$this->id, $method, $method]);
    }

    /**
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
        DB::set("
            UPDATE `investors` SET `phone` = ? 
            WHERE `id` = ?
        ", [$phone, $this->id]);
    }

    /**
     * return array of investors from target investor to (without target)
     * @param int $investor_id
     * @return Investor[]
     */
    static public function referrersToRoot($investor_id)
    {
        $investorsData = @DB::get("
            SELECT
                `investors`.*,
                (
                    SELECT CONCAT(
                        '[',
                        GROUP_CONCAT(JSON_OBJECT('coin', coin, 'sum', sum)),
                        ']'
                    ) FROM `investors_referrals_totals` WHERE `investor_id` = `investors`.`id`
                ) as `referrals_totals`
            FROM `investors`
            WHERE FIND_IN_SET (`id`, (
                SELECT `referrers`
                FROM `investors_referrers`
                WHERE `investor_id` = ?
            ))
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
        ;", [$addedTokensCount, Coin::token(), $this->id]);

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
        if ($ethBounty < 0) {
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

        $data = [
            'ethToReinvest' => $ethToReinvest,
            'tokens' => $tokens
        ];
        EthQueue::sendEthBounty(EthQueue::TYPE_SENDETH_REINVEST, $this->id, $data, ETH_BOUNTY_COLD_WALLET, $ethToReinvest);
        // do mint tokens inside sendEthBounty callback -> EthQueue::checkQueue()
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

        $data = [
            'ethToWithdraw' => $eth
        ];
        EthQueue::sendEthBounty(EthQueue::TYPE_SENDETH_WITHDRAW, $this->id, $data, $this->eth_address, $eth);

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

    public function all_referrals()
    {
        Utility::mkdir_0777(self::ALLREFERRALS_CACHE_PATH_TO_DIR);
        $cacheFile = self::ALLREFERRALS_CACHE_PATH_TO_DIR . "/{$this->id}.json";

        if (is_file($cacheFile)) {
            $cacheContent = file_get_contents($cacheFile);
            $cacheData = @json_decode($cacheContent, true);
            if (isset($cacheData['time'])) {
                if (time() - $cacheData['time'] < self::ALLREFERRALS_CACHE_TIMEOUT) {
                    foreach ($cacheData['investorsData'] as $investorData) {
                        self::createInvestorForGetByIdArr($investorData);
                    }
                    return count($cacheData['investorsData']);
                }
            }
        }

        $str = @DB::get("
            SELECT referrals FROM `investors_referrals`
            WHERE
                `investor_id` = ?
        ;", [$this->id])[0]['referrals'];
        $arr = explode(',', $str);

        $investorsData = self::getByIdArr($arr);

        $dataToCache = [
            'time' => time(),
            'investorsData' => $investorsData
        ];

        file_put_contents($cacheFile, json_encode($dataToCache, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        return count($arr);
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