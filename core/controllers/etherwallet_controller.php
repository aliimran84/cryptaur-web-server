<?php

namespace core\controllers;

use core\engine\Application;
use core\engine\Email;
use core\engine\Utility;
use core\engine\DB;
use core\engine\Router;
use core\models\EtherWallet;
use core\models\EthQueue;
use core\models\Investor;
use core\sfchecker\ACTION2FA;
use core\translate\Translate;
use core\views\Base_view;
use core\views\Investor_view;
use core\views\Menu_point;

class EtherWallet_controller
{
    static public $initialized = false;

    const SEND_WALLET = 'wallet/send';

    static public function init()
    {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        Router::register(function () {
            self::handleSendWallet();
        }, self::SEND_WALLET, Router::POST_METHOD);
    }

    static private function handleSendWallet()
    {
        if (!Application::$authorizedInvestor) {
            Utility::location();
        }
        $send_type = @$_POST['send_type'];
        $amount = @$_POST['amount'];
        $address = @$_POST['address'];
        
        if (!ACTION2FA::access2FAChecker()) {
            session_start();
            $_SESSION[ACTION2FA::TEMP_DATA_ARR] = [];
            $_SESSION[ACTION2FA::TEMP_DATA_ARR]['send_type'] = $send_type;
            $_SESSION[ACTION2FA::TEMP_DATA_ARR]['amount'] = $amount;
            $_SESSION[ACTION2FA::TEMP_DATA_ARR]['address'] = $address;
            session_write_close();
            ACTION2FA::action2FAVerify(self::SEND_WALLET);
        }
        
        $wallet = EtherWallet::getByInvestorId(Application::$authorizedInvestor->id);
        switch ($send_type) {
            case 'ETH':
                $wallet->sendEth($amount, $address);
                break;
            case 'CPT':
                $wallet->sendCpt($amount, $address);
                break;

        }
        Utility::location(Investor_controller::CRYPTAURETHERWALLET_URL);
    }
}