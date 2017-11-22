<?php

namespace core\models;

use core\engine\Application;
use core\engine\DB;
use core\engine\Router;

class Investor
{
    static public $module_initialized = false;

    public $id = 0;
    public $referrer_id = 0;
    public $refferer_code = '';
    public $joined_datetime = 0;
    public $email = '';
    public $tokens_count = 0;
    public $eth_address = '';
    public $eth_withdrawn = 0;

    static public function module_initializing()
    {
        if (Investor::$module_initialized) {
            return;
        }
        Investor::$module_initialized = true;
        Router::register(function () {
            $data = @Application::decodeData($_GET['d']);
            if (!$data) {
                echo 'Perhaps the link is outdated';
                return;
            }
            $investorId = self::registerUser($data['email'], $data['eth_address'], $data['referrer_id']);
            if (!$investorId) {
                echo 'Sorry, something went wrong with investor registration';
                return;
            }
            echo "Registered $investorId!";
        }, "investor/register");
    }

    static public function db_initializing()
    {
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `referrer_id` int(10) UNSIGNED NOT NULL,
                `refferer_code` varchar(32) NOT NULL,
                `joined_datetime` datetime(0) NOT NULL,
                `email` varchar(254) NOT NULL,
                `eth_address` varchar(50) NOT NULL,
                `eth_withdrawn` bigint(20) NOT NULL,
                `tokens_count` bigint(20) UNSIGNED NOT NULL,
                PRIMARY KEY (`id`)
            );
        ");
    }

    /**
     * @param string $email
     * @param string $eth_address
     * @param int $referrer_id
     * @return false|int
     */
    static public function registerUser($email, $eth_address, $referrer_id)
    {
        if (!preg_match("/^0x[a-fA-F0-9]{40}$/", $eth_address)) {
            return false;
        }

        $existingParamsUser = @DB::get("
            SELECT * FROM `investors`
            WHERE
                `email` = ? OR
                `eth_address` = ?
            LIMIT 1
        ;", [$email, $eth_address])[0];
        if ($existingParamsUser) {
            return false;
        }

        if ($referrer_id) {
            $existingReferrer = @DB::get("
                SELECT * FROM `investors`
                WHERE
                    `referrer_id` = ?
                LIMIT 1
            ;", [$referrer_id])[0];
            if (!$existingReferrer) {
                return false;
            }
        }

        $referrer_code = self::generateReferrerCode();

        DB::set("
            INSERT INTO `investors`
            SET
                `referrer_id` = ?,
                `refferer_code`=?,
                `joined_datetime`=?,
                `email`=?,
                `eth_address`=?,
                `eth_withdrawn`=?,
                `tokens_count`=?
            ", [$referrer_id, $referrer_code, DB::timetostr(time()), $email, $eth_address, 0, 0]
        );

        return DB::lastInsertId();
    }

    static private function generateReferrerCode()
    {
        $referrerCode = null;
        do {
            $referrerCode = substr(uniqid(), -9);
        } while (DB::get("SELECT * FROM `investors` WHERE `refferer_code` = ? LIMIT 1;", [$referrerCode]));
        return $referrerCode;
    }

    static public function createUrlForRegistration($email, $eth_address, $referrer_id)
    {
        $data = [
            'email' => $email,
            'eth_address' => $eth_address,
            'referrer_id' => $referrer_id
        ];
        return APPLICATION_URL . "/investor/register?d=" . Application::encodeData($data);
    }
}