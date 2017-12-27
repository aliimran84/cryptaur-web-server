<?php

namespace core\controllers;

use core\engine\Application;
use core\engine\Router;
use core\engine\Utility;
use core\models\Bounty;
use core\models\Deposit;
use core\models\Investor;
use JsonRpc\Client;

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
            Utility::location(Dashboard_controller::BASE_URL);
        }
        if (Application::$authorizedInvestor->eth_bounty == 0) {
            Utility::location(Dashboard_controller::BASE_URL);
        }
        if (!Bounty::withdrawIsOn() || !Bounty::reinvestIsOn()) {
            Utility::location(Dashboard_controller::BASE_URL);
        }
        $ethToReinvest = Application::$authorizedInvestor->eth_bounty * $percentsForReinvesting / 100;
        $ethToWithdraw = Application::$authorizedInvestor->eth_bounty - $ethToReinvest;
        if ($ethToWithdraw > 0) {
            Application::$authorizedInvestor->withdraw($ethToWithdraw);
        }
        if ($ethToReinvest > 0) {
            Application::$authorizedInvestor->reinvestEth($ethToReinvest);
        }
        Utility::location(Dashboard_controller::BASE_URL);
    }

    /**
     * @param Investor $investor
     * @param double $tokens
     * @param Deposit $deposit
     * @return int if > 0 -> success
     */
    static public function mintTokens(&$investor, $tokens, &$deposit = null)
    {
        var_dump('mint');
        exit;
        $gethClient = new Client(ETH_TOKENS_NODE_URL);

        if (!$gethClient->call('personal_unlockAccount', [
            ETH_TOKENS_WALLET,
            ETH_TOKENS_PASSWORD,
            5
        ])) {
            return -1;
        }
        if (!$gethClient->result) {
            return -2;
        }

        // https://github.com/ethereum/wiki/wiki/Ethereum-Contract-ABI
        // function mintTokens(address _minter, uint _tokens, uint8 _originalCoinType, bytes32 _originalTxHash)
        // mintTokens(address,uint256,uint8,bytes32)
        // $mintTokens_keccak256 = '24b35ef299a7626e8b3733ca6233658c9943b9876310b76bc78416948e554ed8';
        $mintTokens_selector = '24b35ef2';
        $minter = $investor->eth_address;
        $tokensWithoutDecimals = (double)($tokens * pow(10, 8));
        $coin = '';
        $txid = '';
        if (!is_null($deposit)) {
            $coin = $deposit->coin;
            $txid = $deposit->txid;
        }

        $minter_geth = str_pad(preg_replace('/^0x(.*)$/', '$1', $minter), 64, '0', STR_PAD_LEFT);
        $tokens_geth = str_pad(dechex($tokensWithoutDecimals), 64, '0', STR_PAD_LEFT);
        $coin_geth = str_pad(bin2hex($coin), 64, '0', STR_PAD_LEFT);
        $txid_geth = str_pad(preg_replace('/^0x(.*)$/', '$1', $txid), 64, '0', STR_PAD_LEFT);

        $mint_call = "0x$mintTokens_selector$minter_geth$tokens_geth$coin_geth$txid_geth";

        if (!$gethClient->call('eth_sendTransaction', [
            [
                'from' => ETH_TOKENS_WALLET,
                'to' => ETH_TOKENS_CONTRACT,
                'value' => '0x0',
                'data' => $mint_call,
                'gas' => "0x" . dechex(500000)
            ]
        ])) {
            return -3;
        }

        if ($gethClient->result === '0x') {
            return -4;
        }
        return 1;
    }

    /**
     * @param string $ethAddress (use ETH_BOUNTY_COLD_WALLET or investor address)
     * @param double $value in Eth
     * @return int if > 0 -> success
     */
    static public function sendEth($ethAddress, $value)
    {
        $gethClient = new Client(ETH_BOUNTY_NODE_URL);

        if (!$gethClient->call('personal_unlockAccount', [
            ETH_BOUNTY_DISPENSER,
            ETH_BOUNTY_PASSWORD,
            5
        ])) {
            return -1;
        }
        if (!$gethClient->result) {
            return -2;
        }

        if (!$gethClient->call('eth_sendTransaction', [
            [
                'from' => ETH_BOUNTY_DISPENSER,
                'to' => $ethAddress,
                'value' => "0x" . Utility::bcdechex(\bcmul($value, '1000000000000000000')),
                'gas' => "0x" . dechex(100000)
            ]
        ])) {
            return -3;
        }

        if ($gethClient->result === '0x') {
            return -4;
        }
        return 1;
    }
}