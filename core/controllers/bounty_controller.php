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
     * @param Deposit $deposit
     * @return bool
     */
    static public function mintTokens($investor, $tokens, $deposit)
    {
        $gethClient = new Client(BOUNTY_ETH_MINT_NODE_URL);

        if (!$gethClient->call('personal_unlockAccount', [
            BOUNTY_ETH_BACKEND_WALLET,
            BOUNTY_ETH_BACKEND_PASSWORD,
            5
        ])) {
            return false;
        }
        if (!$gethClient->result) {
            return false;
        }

        // https://github.com/ethereum/wiki/wiki/Ethereum-Contract-ABI
        // function mintTokens(address _minter, uint _tokens, uint8 _originalCoinType, bytes32 _originalTxHash)
        // mintTokens(address,uint256,uint8,bytes32)
        // $mintTokens_keccak256 = '24b35ef299a7626e8b3733ca6233658c9943b9876310b76bc78416948e554ed8';
        $mintTokens_selector = '24b35ef2';
        $minter = $investor->eth_address;
        $tokensWithoutDecimals = floor($tokens * pow(10, 8));

        $minter_geth = str_pad(preg_replace('/^0x(.*)$/', '$1', $minter), 64, '0', STR_PAD_LEFT);
        $tokens_geth = str_pad(dechex($tokensWithoutDecimals), 64, '0', STR_PAD_LEFT);
        $coin_geth = str_pad(bin2hex($deposit->coin), 64, '0', STR_PAD_LEFT);
        $txid_geth = str_pad(preg_replace('/^0x(.*)$/', '$1', $deposit->txid), 64, '0', STR_PAD_LEFT);

        $mint_call = "0x$mintTokens_selector$minter_geth$tokens_geth$coin_geth$txid_geth";

        if (!$gethClient->call('eth_sendTransaction', [
            [
                'from' => BOUNTY_ETH_BACKEND_WALLET,
                'to' => BOUNTY_ETH_CRYPTAUR_CONTRACT,
                'value' => '0x0',
                'data' => $mint_call,
                'gas' => "0x" . dechex(100000)
            ],
        ])) {
            return false;
        }

        return $gethClient->result !== '0x';
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