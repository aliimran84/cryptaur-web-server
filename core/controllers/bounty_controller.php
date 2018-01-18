<?php

namespace core\controllers;

use core\engine\Application;
use core\engine\Router;
use core\engine\Utility;
use core\models\Bounty;
use core\models\Deposit;
use core\models\Investor;
use core\views\Dashboard_view;
use JsonRpc\Client;

class Bounty_controller
{
    static public $initialized = false;
    const GAS_PRICE = 0.00000005;
    const GAS_TO_MINT = 900000;
    const GAS_TO_SENDETH = 400000;
    const ETH_TO_WEI = '1000000000000000000';

    const INVESTOR_REALIZE_URL = 'bounty/investor_realize';

    static public function init()
    {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        require(PATH_TO_THIRD_PARTY_DIR . '/JsonRpc/loader.php');

        Router::register(function () {
            if (!Application::$authorizedInvestor) {
                Utility::location(Investor_controller::BASE_URL);
            }
            self::handleInvestorBountyRealizeRequest();
        }, self::INVESTOR_REALIZE_URL, Router::POST_METHOD);
    }

    static private function handleInvestorBountyRealizeRequest()
    {
        if (@$_SESSION['tester']) {
            Utility::location(Dashboard_controller::BASE_URL . '?' . Dashboard_view::BOUNTY_ERR . '=7251');
        }

        $percentsForReinvesting = (int)@$_POST['percentsForReinvesting'];
        if ($percentsForReinvesting < 0 && $percentsForReinvesting > 100) {
            Utility::location(Dashboard_controller::BASE_URL . '?' . Dashboard_view::BOUNTY_ERR . '=7252');
        }
        if (Application::$authorizedInvestor->eth_bounty == 0) {
            Utility::location(Dashboard_controller::BASE_URL . '?' . Dashboard_view::BOUNTY_ERR . '=7253');
        }
        if (!Bounty::withdrawIsOn() || !Bounty::reinvestIsOn()) {
            Utility::location(Dashboard_controller::BASE_URL . '?' . Dashboard_view::BOUNTY_ERR . '=7254');
        }
        $ethToReinvest = Utility::minPrecisionNumber(Application::$authorizedInvestor->eth_bounty * ($percentsForReinvesting / 100));
        $ethToWithdraw = Application::$authorizedInvestor->eth_bounty - $ethToReinvest;

        if ($ethToWithdraw > 0) {
            if (!Application::$authorizedInvestor->withdraw($ethToWithdraw)) {
                Utility::location(Dashboard_controller::BASE_URL . '?' . Dashboard_view::BOUNTY_ERR . '=7256');
            }
        }
        if ($ethToReinvest > 0) {
            if (!Application::$authorizedInvestor->reinvestEth($ethToReinvest)) {
                Utility::location(Dashboard_controller::BASE_URL . '?' . Dashboard_view::BOUNTY_ERR . '=7257');
            }
        }

        Utility::location(Dashboard_controller::BASE_URL);
    }
}