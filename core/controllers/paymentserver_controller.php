<?php

namespace core\controllers;

use core\engine\Application;
use core\engine\Utility;
use core\engine\Router;
use core\models\PaymentServer;

class PaymentServer_controller
{
    static public $initialized = false;

    const SET_URL = 'paymentserver/set';

    static public function init()
    {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        Router::register(function () {
            // administrators can setup only administrator
            if (!Application::$authorizedAdministrator) {
                Utility::location();
            }
            self::handleSetRequest();
        }, self::SET_URL, Router::POST_METHOD);
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
}