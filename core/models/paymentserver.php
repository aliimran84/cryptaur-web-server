<?php

namespace core\models;

use core\engine\DB;

class PaymentServer
{
    public $id;
    public $url = '';
    public $nonce = 0;
    public $keyid = '';
    public $secretkey = '';

    private function __construct()
    {
    }

    static public function db_init()
    {
        DB::query("
            CREATE TABLE IF NOT EXISTS `payment_servers` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `url` varchar(254) DEFAULT '',
                `nonce` int(10) UNSIGNED DEFAULT '0',
                `keyid` varchar(254) DEFAULT '',
                `secretkey` varchar(254) DEFAULT '',
                PRIMARY KEY (`id`),
                UNIQUE KEY `keyid` (`keyid`)
            );
        ");
    }

    /**
     * @param string $url
     * @param string $keyId
     * @param string $secretKey
     */
    static public function set($url, $keyId, $secretKey)
    {
        if (self::getFirst()) {
            DB::set("
                UPDATE `payment_servers` SET
                    `url` = ?,
                    `secretkey` = ?,
                    `keyid` = ?
            ;", [$url, $secretKey, $keyId]);
        } else {
            DB::set("
                INSERT INTO `payment_servers` SET
                    `url` = ?,
                    `secretkey` = ?,
                    `keyid` = ?
            ;", [$url, $secretKey, $keyId]);
        }

        return;
        // когда серверов станет больше

        if (self::getByKeyId($keyId)) {
            DB::set("
                UPDATE `payment_servers` SET
                    `url` = ?,
                    `secretkey` = ?
                WHERE
                    `keyid` = ?
            ;", [$url, $secretKey, $keyId]);
        } else {
            DB::set("
                INSERT INTO `payment_servers` SET
                    `url` = ?,
                    `secretkey` = ?,
                    `keyid` = ?
            ;", [$url, $secretKey, $keyId]);
        }
    }

    static private function createWithDataFromDB($data)
    {
        $instance = new PaymentServer();
        $instance->id = $data['id'];
        $instance->url = $data['url'];
        $instance->nonce = $data['nonce'];
        $instance->keyid = $data['keyid'];
        $instance->secretkey = $data['secretkey'];
        return $instance;
    }

    /**
     * @param string $keyId
     * @return PaymentServer|null
     */
    static public function getByKeyId($keyId)
    {
        $data = @DB::get("
            SELECT * FROM `payment_servers`
            WHERE
                `keyid` = ?
            LIMIT 1
        ;", [$keyId])[0];

        if (!$data) {
            return null;
        }

        return self::createWithDataFromDB($data);
    }

    /**
     * @return PaymentServer|null
     */
    static public function getFirst()
    {
        $data = @DB::get("SELECT * FROM `payment_servers` LIMIT 1;")[0];
        if (!$data) {
            return null;
        }
        return self::createWithDataFromDB($data);
    }

    /**
     * check that new nonce bigger than old and update to new
     * @param string $keyId
     * @param int $newNonce
     * @return bool
     */
    static public function checkUpdateNonce($keyId, $newNonce)
    {
        $pServer = self::getByKeyId($keyId);
        if (!$pServer) {
            return false;
        }
        if ($pServer->nonce >= $newNonce) {
            return false;
        }
        $pServer->nonce = $newNonce;
        DB::set("UPDATE `payment_servers` SET `nonce`=? WHERE `keyid`=? LIMIT 1;", [$newNonce, $keyId]);
        return true;
    }
}