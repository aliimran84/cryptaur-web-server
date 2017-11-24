<?php

namespace core\controllers;

use core\engine\Application;
use core\engine\Utility;
use core\engine\Router;
use core\models\Deposit;
use core\models\PaymentServer;
use core\models\Wallet;

class PaymentServer_controller
{
    static public $initialized = false;

    const NOTIFY_URL = 'paymentserver/notify';
    const SET_URL = 'paymentserver/set';

    const NOTIFY_REASON_ADDRESS = 'address';
    const NOTIFY_REASON_DEPOSIT = 'deposit';
    const HMAC_HEADER = 'HMAC-Signature';

    static public function init()
    {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        Router::register(function () {
            if (!Application::$authorizedAdministrator) {
                Utility::location();
            }
            self::handleSetRequest();
        }, self::SET_URL, Router::POST_METHOD);


        Router::register(function () {
            $result = self::handleNotify();
            Utility::logOriginalRequest('paymentServerNotify/' . time(), $result);
            $accept = $result > 0;
            echo json_encode(['accept' => $accept], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }, self::NOTIFY_URL);
    }

    static public function handleSetRequest()
    {
        PaymentServer::set(trim($_POST['url'], '/'), $_POST['keyid'], $_POST['secretkey']);
        Utility::location(Administrator_controller::COINS_SETTINGS);
    }

    /**
     * @param string $keyId
     * @param string $message full php-in
     * @return bool|string
     */
    static public function messageHmacHash($keyId, $message)
    {
        $pServer = PaymentServer::getByKeyId($keyId);
        if (!$pServer) {
            return false;
        }
        return hash_hmac('sha256', $message, pack("H*", $pServer->secretkey));
    }

    /**
     * @return int < 0 - error; > 0 success
     */
    static public function handleNotify()
    {
        $headers = getallheaders();
        $receivedHmacHash = @$headers[self::HMAC_HEADER];
        $rawMessage = @file_get_contents('php://input');
        $message = @json_decode($rawMessage, true);
        if (!$message) {
            return -1;
        }
        $calculatedHmac = self::messageHmacHash($message['keyid'], $rawMessage);
        if ($receivedHmacHash !== $calculatedHmac) {
            return -2;
        }

        if (!PaymentServer::checkUpdateNonce($message['keyid'], $message['nonce'])) {
            return -3;
        }

        if ($message['reason'] === self::NOTIFY_REASON_ADDRESS) {
            //"{"address": "0xf410e1a3b6a42511d2113911111181116511711d", "coin": "eth", "keyid": "22c336448554e86b", "nonce": 1511527643485, "reason": "address", "userid": 1}"
            Wallet::registerWallet($message['userid'], $message['coin'], $message['address']);
            return 1;
        } else if ($message['reason'] === self::NOTIFY_REASON_DEPOSIT) {
            //"{"amount": "2.0", "coin": "DOGE", "conf": 1, "keyid": "22c336448554e86b", "nonce": 1511352641577, "reason": "deposit", "txid": "cf3344b4fd3d7cf08fbd3fc19a751a0cf7ec1d776e65bcf3c573bac5c6484f5d", "userid": 666, "vout": 0}"
            if (!Deposit::receiveDeposit((double)$message['amount'], $message['coin'], $message['conf'], $message['txid'], $message['vout'], $message['userid'])) {
                return -4;
            }
            return 2;
        }

        return -5;
    }

    /**
     * @param PaymentServer $pServer
     * @param string $coin
     * @param int $userId
     * @return bool|array
     */
    static public function requestWalletRegistration($pServer, $coin, $userId)
    {
        $lowerCoin = strtolower($coin);
        $httpPostResult = Utility::httpPost("{$pServer->url}/$lowerCoin/getaddress", ['user' => $userId]);
        $response = @json_decode(
            $httpPostResult,
            true
        )['result'];
        if (!$response) {
            return false;
        }
        if (!isset($response['pending']) || !isset($response['address'])) {
            return false;
        }
        return $response;
    }
}