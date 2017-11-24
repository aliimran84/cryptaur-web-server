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
                `url` varchar(254) NOT NULL,
                `nonce` int(10) UNSIGNED NOT NULL,
                `keyid` varchar(254) NOT NULL,
                `secretkey` varchar(254) NOT NULL,
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
        DB::set("
            REPLACE `payment_servers` SET
            `url` = ?,
            `keyid` = ?,
            `secretkey` = ?
        ;", [$url, $keyId, $secretKey]);
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

    /**
     * @param string $keyId
     * @param string $message full php-in
     * @return bool|string
     */
    static public function messageHmacHash($keyId, $message)
    {
        $pServer = self::getByKeyId($keyId);
        if (!$pServer) {
            return false;
        }
        return hash_hmac('sha256', $message, pack("H*", $pServer->secretkey));
    }
}