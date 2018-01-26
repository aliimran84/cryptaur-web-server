<?php

namespace core\sfchecker;

use core\engine\Utility;
use core\engine\Router;
use core\controllers\Investor_controller;

class ACTION2FA
{
    const TEMP_DATA_ARR = 'temp_data_arr';
    const TEMP_DATA_URL = 'temp_data_url';
    const TEMP_DATA_METHOD = 'temp_data_method';
    const LAST_SECURED_TIME= 'last_secured_time';
    
    static private function writeToPost()
    {
        if (
            isset($_SESSION[self::TEMP_DATA_ARR])
            && is_array($_SESSION[self::TEMP_DATA_ARR])
        ) {
            foreach ($_SESSION[self::TEMP_DATA_ARR] AS $key => $value) {
                $_POST[$key] = $value;
            }
            session_start();
            unset($_SESSION[self::TEMP_DATA_ARR]);
            session_write_close();
        }
    }
    
    static private function readFromPost()
    {
        if (count($_POST) > 0) {
            session_start();
            $_SESSION[self::TEMP_DATA_ARR] = [];
            foreach ($_POST AS $key => $value) {
                $_SESSION[self::TEMP_DATA_ARR][$key] = $value;
            }
            session_write_close();
        }
    }

    static private function action2FAVerify()
    {
        $sfa_form = Investor_controller::investor2FAFormType();
        if (USE_2FA == FALSE || is_null($sfa_form)) {
            return NULL;
        } else {
            if ($sfa_form == 0) {
                Utility::location(Investor_controller::SECONDFACTOR_URL);
            } else {
                Utility::location(Investor_controller::SECONDFACTORDUAL_URL);
            }
        }
    }
    
    static public function access2FAChecker($url, $method)
    {
        if (
            (isset($_SESSION[self::LAST_SECURED_TIME]) && time() - $_SESSION[self::LAST_SECURED_TIME] < SECURED_SESSION_TIME)
            || USE_2FA == FALSE
        ) {
            return NULL;
        } else {
            session_start();
            $_SESSION[self::TEMP_DATA_URL] = $url;
            $_SESSION[self::TEMP_DATA_METHOD] = $method;
            session_write_close();
            self::readFromPost();
            self::action2FAVerify();
        }
    }
    
    static public function smart2FARedirect()
    {
        session_start();
        $_SESSION[self::LAST_SECURED_TIME] = time();
        session_write_close();
        
        if (
            isset($_SESSION[self::TEMP_DATA_URL])
            || isset($_SESSION[self::TEMP_DATA_METHOD])
        ) {
            $url = $_SESSION[self::TEMP_DATA_URL];
            $method = $_SESSION[self::TEMP_DATA_METHOD];
            session_start();
            unset($_SESSION[self::TEMP_DATA_URL]);
            unset($_SESSION[self::TEMP_DATA_METHOD]);
            session_write_close();
            self::writeToPost();
            call_user_func(Router::getByPathAndMethod($url, $method));
        }
    }
}