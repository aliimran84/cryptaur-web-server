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
        $wallet = EtherWallet::getByInvestorId(Application::$authorizedInvestor->id);
        $amount = @$_POST['amount'];
        switch (@$_POST['send_type']) {
            case 'ETH':
                $wallet->sendEth($amount, @$_POST['address']);
                break;
            case 'CPT':
                $wallet->sendCpt($amount, @$_POST['address']);
                break;

        }
        Utility::location(Investor_controller::CRYPTAURETHERWALLET_URL);
    }
}