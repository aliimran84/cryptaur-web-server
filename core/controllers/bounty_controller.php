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
        $ethToWithdraw = Utility::minPrecisionNumber(Application::$authorizedInvestor->eth_bounty - $ethToReinvest);

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

    /**
     * @param string $ethAddress (use ETH_BOUNTY_COLD_WALLET or investor address)
     * @param double $value in Eth
     * @return array(int, string) if int < 0 -> error, string - txid
     */
    static public function sendEth($ethAddress, $value)
    {
        $gethClient = new Client(ETH_BOUNTY_NODE_URL);

        if (!$gethClient->call('personal_unlockAccount', [
            ETH_BOUNTY_DISPENSER,
            ETH_BOUNTY_PASSWORD,
            5
        ])) {
            return [-1, "{$gethClient->errorCode}: {$gethClient->error}"];
        }
        if (!$gethClient->result) {
            return [-2, ''];
        }

        Utility::log('geth1/' . Utility::microtime_float(), [
            '_value' => $value,
            'from' => ETH_BOUNTY_DISPENSER,
            'to' => $ethAddress,
            'value' => "0x" . Utility::hex(Utility::mul($value, self::ETH_TO_WEI)),
            'gas' => "0x" . Utility::hex(self::GAS_TO_SENDETH),
            'gasPrice' => "0x" . Utility::hex(Utility::mul(self::GAS_PRICE, self::ETH_TO_WEI))
        ]);
        if (!$gethClient->call('eth_sendTransaction', [
            [
                'from' => ETH_BOUNTY_DISPENSER,
                'to' => $ethAddress,
                'value' => "0x" . Utility::hex(Utility::mul($value, self::ETH_TO_WEI)),
                'gas' => "0x" . Utility::hex(self::GAS_TO_SENDETH),
                'gasPrice' => "0x" . Utility::hex(Utility::mul(self::GAS_PRICE, self::ETH_TO_WEI))
            ]
        ])) {
            return [-3, "{$gethClient->errorCode}: {$gethClient->error}"];
        }

        if ($gethClient->result === '0x') {
            return [-4, $gethClient->result];
        }

        return [0, $gethClient->result];
    }
}