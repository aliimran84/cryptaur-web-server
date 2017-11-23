<?php

namespace core\models;

use core\engine\DB;

class Investor
{
    public $id = 0;
    public $referrer_id = 0;
    public $referrer_code = '';
    public $joined_datetime = 0;
    public $email = '';
    public $tokens_count = 0;
    public $eth_address = '';
    public $eth_withdrawn = 0;

    static public function getById($id)
    {
        $investor = @DB::get("
            SELECT * FROM `investors`
            WHERE
                `id` = ?
            LIMIT 1
        ;", [$id])[0];

        if (!$investor) {
            return null;
        }

        $instance = new Investor();
        $instance->id = $investor['id'];
        $instance->referrer_id = $investor['referrer_id'];
        $instance->referrer_code = $investor['referrer_code'];
        $instance->joined_datetime = strtotime($investor['joined_datetime']);
        $instance->email = $investor['email'];
        $instance->tokens_count = $investor['tokens_count'];
        $instance->eth_address = $investor['eth_address'];
        $instance->eth_withdrawn = $investor['eth_withdrawn'];

        return $instance;
    }

    static public function db_init()
    {
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `referrer_id` int(10) UNSIGNED NOT NULL,
                `referrer_code` varchar(32) NOT NULL,
                `joined_datetime` datetime(0) NOT NULL,
                `email` varchar(254) NOT NULL,
                `password_hash` varchar(254) NOT NULL,
                `eth_address` varchar(50) NOT NULL,
                `eth_withdrawn` double(20, 8) NOT NULL,
                `tokens_count` bigint(20) UNSIGNED NOT NULL,
                `phone` varchar(254) NOT NULL,
                PRIMARY KEY (`id`)
            );
        ");
    }

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

    static public function isExistInvestorWithParams($email, $eth_address)
    {
        return !!@DB::get("
            SELECT * FROM `investors`
            WHERE
                `email` = ? OR
                `eth_address` = ?
            LIMIT 1
        ;", [$email, $eth_address])[0];
    }
}