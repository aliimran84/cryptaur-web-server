<?php

namespace core\sfchecker;

use core\engine\Application;
use core\models\EtherWallet;
use core\engine\Utility;
use core\controllers\Investor_controller;
use core\controllers\EtherWallet_controller;
use core\controllers\Bounty_controller;
use core\controllers\Dashboard_controller;
use core\views\Dashboard_view;

class ACTION2FA
{
    const TEMP_DATA_ARR = 'temp_data_arr';
    const LAST_SECURED_TIME= 'last_secured_time';
    
    static public function action2FAVerify($url)
    {
        $sfa_form = Investor_controller::investor2FAFormType();
        if (USE_2FA == FALSE || is_null($sfa_form)) {
            return NULL;
        } else {
            session_start();
            $_SESSION[Investor_controller::SESSION_KEY_TMP] = $url;
            session_write_close();
            if ($sfa_form == 0) {
                Utility::location(Investor_controller::SECONDFACTOR_URL);
            } else {
                Utility::location(Investor_controller::SECONDFACTORDUAL_URL);
            }
        }
    }
    
    static public function access2FAChecker()
    {
        if (
            (isset($_SESSION[self::LAST_SECURED_TIME]) && time() - $_SESSION[self::LAST_SECURED_TIME] < SECURED_SESSION_TIME)
            || USE_2FA == FALSE
        ) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    static public function smart2FARedirect($url)
    {
        session_start();
        $_SESSION[self::LAST_SECURED_TIME] = time();
        session_write_close();
        
        switch ($url) {
            case Investor_controller::SETTINGS_URL :
                Utility::location(Investor_controller::SETTINGS_URL);
                break;
            
            case EtherWallet_controller::SEND_WALLET :
                if (
                    !isset($_SESSION[self::TEMP_DATA_ARR]['send_type'])
                    || !isset($_SESSION[self::TEMP_DATA_ARR]['amount'])
                    || !isset($_SESSION[self::TEMP_DATA_ARR]['address'])
                ) {
                    break;
                }
                
                $send_type = $_SESSION[self::TEMP_DATA_ARR]['send_type'];
                $amount = $_SESSION[self::TEMP_DATA_ARR]['amount'];
                $address = $_SESSION[self::TEMP_DATA_ARR]['address'];
                
                session_start();
                unset($_SESSION[self::TEMP_DATA_ARR]);
                session_write_close();
                
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
                break;
            
            case Bounty_controller::INVESTOR_REALIZE_URL :
                if (
                    !isset($_SESSION[self::TEMP_DATA_ARR]['percentsForReinvesting'])
                ) {
                    break;
                }

                $percentsForReinvesting = $_SESSION[self::TEMP_DATA_ARR]['percentsForReinvesting'];

                session_start();
                unset($_SESSION[self::TEMP_DATA_ARR]);
                session_write_close();

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
                break;

        }
    }
}