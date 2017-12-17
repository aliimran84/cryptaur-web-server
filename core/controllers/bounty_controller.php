<?php

namespace core\controllers;

use core\engine\Application;
use core\engine\Router;
use core\engine\Utility;
use core\models\Bounty;
use core\models\Investor;

class Bounty_controller
{
    static public $initialized = false;

    const INVESTOR_REALIZE_URL = 'bounty/investor_realize';

    static public function init()
    {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        Router::register(function () {
            if (!Application::$authorizedInvestor) {
                Utility::location(Investor_controller::BASE_URL);
            }
            self::handleInvestorBountyRealizeRequest();
        }, self::INVESTOR_REALIZE_URL, Router::POST_METHOD);
    }

    static private function handleInvestorBountyRealizeRequest()
    {
        $ethAmount = (double)@$_POST['amount'];
        if ($ethAmount > 0) {
            if (@$_POST['action'] === 'withdraw') {
                if (Bounty::withdrawIsOn()) {
                    Application::$authorizedInvestor->withdraw($ethAmount);
                }
            } else if (@$_POST['action'] === 'reinvest') {
                if (Bounty::reinvestIsOn()) {
                    Application::$authorizedInvestor->reinvestEth($ethAmount);
                }
            }
        }
        Utility::location(Dashboard_controller::BASE_URL);
    }

    /**
     * @param Investor $investor
     * @param double $tokens
     * @param string $coin
     * @param string $txid
     * @return bool
     */
    static public function mintTokens($investor, $tokens, $coin, $txid)
    {
        //todo: body
        return true;
    }

    /**
     * @param string $ethAddress (use BOUNTY_ETH_REINVESTOR_WALLET or investor address)
     * @param double $value
     * @return bool
     */
    static public function sendEth($ethAddress, $value)
    {
        //todo: body
        return true;
    }
}