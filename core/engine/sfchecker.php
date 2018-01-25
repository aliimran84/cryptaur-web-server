<?php

namespace core\sfchecker;

use core\engine\Application;
use core\models\EtherWallet;
use core\engine\Utility;
use core\controllers\Investor_controller;

class ACTION2FA
{
    const TEMP_DATA_ARR = 'temp_data_arr';
    
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
    
    static public function access2FAChecker($url)
    {
        if (isset($_SESSION[$url]) || USE_2FA == FALSE) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    static public function access2FAClear($url)
    {
        session_start();
        if (isset($_SESSION[$url])) {
            unset($_SESSION[$url]);
        }
        session_write_close();
    }
    
    static public function access2FAAllow($url)
    {
        session_start();
        $_SESSION[$url] = 1;
        session_write_close();
    }
    
    static public function smart2FARedirect($url)
    {
        switch ($url) {
            case Investor_controller::SETTINGS_URL :
                self::access2FAAllow(Investor_controller::SETTINGS_URL);
                Utility::location(Investor_controller::SETTINGS_URL);
                break;
            case EtherWallet::SEND_WALLET :
                if (
                    !isset($_SESSION[self::TEMP_DATA_ARR]['send_type'])
                    || !isset($_SESSION[self::TEMP_DATA_ARR]['amount'])
                    || !isset($_SESSION[self::TEMP_DATA_ARR]['address'])
                ) {
                    break;
                }
                
                $send_type = $_SESSION[self::TEMP_DATA_ARR]['send_type'];
                $send_type = $_SESSION[self::TEMP_DATA_ARR]['amount'];
                $send_type = $_SESSION[self::TEMP_DATA_ARR]['address'];
                
                session_start();
                unset($_SESSION[self::TEMP_DATA_ARR]);
                session_write_close();
                
                $wallet = EtherWallet::getByInvestorId(Application::$authorizedInvestor->id);
                switch ($send_type) {
                    case 'ETH':
                        $wallet->sendEth($amount, @$_POST['address']);
                        break;
                    case 'CPT':
                        $wallet->sendCpt($amount, @$_POST['address']);
                        break;
                }
                Utility::location(Investor_controller::CRYPTAURETHERWALLET_URL);
                break;
        }
    }
}