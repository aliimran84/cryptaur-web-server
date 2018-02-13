<?php

namespace core\sfchecker;

use core\engine\Router;
use core\engine\Application;
use core\translate\Translate;
use core\secondfactor\API2FA;
use core\secondfactor\variants_2FA;
use core\views\SFchecker_view;
use core\views\Base_view;

class ACTION2FA
{
    const TEMP_DATA_ARR = 'temp_data_arr';
    const TEMP_DATA_URL = 'temp_data_url';
    const TEMP_DATA_METHOD = 'temp_data_method';
    const TEMP_DATA_VARIANT = 'temp_data_variant';
    const TEMP_DATA_TARGET = 'temp_data_target';
    const TEMP_DATA_SENDED = 'temp_data_sended';
    const LAST_SECURED_TIME = 'last_secured_time';
    const LAST_2FA_TRY = '2fa_last_try';
    
    static private function writeToPost()
    {
        if (
            isset($_SESSION[self::TEMP_DATA_ARR])
            && is_array($_SESSION[self::TEMP_DATA_ARR])
        ) {
            foreach ($_SESSION[self::TEMP_DATA_ARR] AS $key => $value) {
                $_POST[$key] = $value;
            }
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
    
    static private function formDraw($message = '')
    {
        echo Base_view::header();
        echo SFchecker_view::secondfactorForm($_SESSION[self::TEMP_DATA_URL], $_SESSION[self::TEMP_DATA_METHOD], $message);
        echo Base_view::footer();
        exit;
    }

    static public function access2FAChecker($url, $method, $variant = NULL, $target = NULL)
    {
        if (
            (isset($_SESSION[self::LAST_SECURED_TIME]) && time() - $_SESSION[self::LAST_SECURED_TIME] < SECURED_SESSION_TIME)
            || USE_2FA == FALSE || (
                isset(Application::$authorizedInvestor->preferred_2fa) && Application::$authorizedInvestor->preferred_2fa == variants_2FA::none
        )) {
            return NULL;
        } else {
            //if 2FA must be used
            if (
                !isset($_SESSION[self::TEMP_DATA_URL]) 
                || !isset($_SESSION[self::TEMP_DATA_METHOD])
            ) {
                //first-time 2FA init
                self::clearSessionData(TRUE); //clean session data to make process clear
                session_start();
                $_SESSION[self::TEMP_DATA_URL] = $url;
                $_SESSION[self::TEMP_DATA_METHOD] = $method;
                if (isset($variant) && isset($target)) {
                    $_SESSION[self::TEMP_DATA_VARIANT] = $variant;
                    $_SESSION[self::TEMP_DATA_TARGET] = $target;
                }
                session_write_close();
                self::readFromPost();
                self::formDraw();
            } elseif (isset($_SESSION[self::TEMP_DATA_URL]) && isset($_SESSION[self::TEMP_DATA_METHOD])) {
                if (isset($_POST['send'])) {
                    //if user trying sent/resent the code(s)
                    $message = "";
                    $time = time();
                    if (isset($_SESSION[self::LAST_2FA_TRY]) && $time - $_SESSION[self::LAST_2FA_TRY] < CODE_SENT_TIME) {
                        $diff = CODE_SENT_TIME - ($time - $_SESSION[self::LAST_2FA_TRY]);
                        $message = Translate::td('You cannot send another code(s) until seconds will expire', ['num' => $diff]);
                        self::formDraw($message);
                    } else {
                        session_start();
                        $_SESSION[self::LAST_2FA_TRY] = $time;
                        $_SESSION[self::TEMP_DATA_SENDED] = 1;
                        session_write_close();
                        $sended;
                        if (
                            isset($_SESSION[self::TEMP_DATA_VARIANT])
                            || isset($_SESSION[self::TEMP_DATA_TARGET])
                        ) {
                            $sended = self::sent2FARequest(
                                $_SESSION[self::TEMP_DATA_VARIANT], 
                                $_SESSION[self::TEMP_DATA_TARGET]
                            );
                        } else {
                            $sended = self::sent2FARequestByInvestor();
                        }
                        if ($sended === TRUE) {
                            $message = Translate::td('Authentication code has been sended using preferred method');
                        } elseif ($sended === FALSE) { //potential case when some methods have been disabled or broken
                            //self::smart2FARedirect();
                            session_start();
                            unset($_SESSION[self::TEMP_DATA_SENDED]);
                            session_write_close();
                            $message = Translate::td('Authentication code has not been sended');
                        }
                        self::formDraw($message);
                    }
                } elseif (isset($_POST['commit']) && isset($_POST['otp'])) {
                    $checked = API2FA::check($_POST['otp']);
                    if ($checked === TRUE) {
                        ACTION2FA::smart2FARedirect();
                    } else {
                        $message = Translate::td('wrong authentication code');
                        if (is_null($checked)) {
                            //if no more tries
                            session_start();
                            unset($_SESSION[self::TEMP_DATA_SENDED]);
                            session_write_close();
                        }
                        self::formDraw($message);
                    }
                } else {
                    self::formDraw();
                }
            }
        }
    }
    
    static public function smart2FARedirect()
    {
        session_start();
        $_SESSION[self::LAST_SECURED_TIME] = time();
        session_write_close();
        if (
            isset($_SESSION[self::TEMP_DATA_URL])
            && isset($_SESSION[self::TEMP_DATA_METHOD])
        ) {
            $url = $_SESSION[self::TEMP_DATA_URL];
            $method = $_SESSION[self::TEMP_DATA_METHOD];
            self::writeToPost();
            self::clearSessionData();
            call_user_func(Router::getByPathAndMethod($url, $method));
            exit;
        }
    }
    
    static public function clearSessionData($secure_clear = FALSE)
    {
        session_start();
        if ($secure_clear === TRUE && isset($_SESSION[self::LAST_SECURED_TIME])) {
            unset($_SESSION[self::LAST_SECURED_TIME]);
        }
        if (isset($_SESSION[self::TEMP_DATA_URL])) {
            unset($_SESSION[self::TEMP_DATA_URL]);
        }
        if (isset($_SESSION[self::TEMP_DATA_METHOD])) {
            unset($_SESSION[self::TEMP_DATA_METHOD]);
        }
        if (isset($_SESSION[self::LAST_2FA_TRY])) {
            unset($_SESSION[self::LAST_2FA_TRY]);
        }
        if (isset($_SESSION[self::TEMP_DATA_VARIANT])) {
            unset($_SESSION[self::TEMP_DATA_VARIANT]);
        }
        if (isset($_SESSION[self::TEMP_DATA_TARGET])) {
            unset($_SESSION[self::TEMP_DATA_TARGET]);
        }
        if (isset($_SESSION[self::TEMP_DATA_ARR])) {
            unset($_SESSION[self::TEMP_DATA_ARR]);
        }
        if (isset($_SESSION[self::TEMP_DATA_SENDED])) {
            unset($_SESSION[self::TEMP_DATA_SENDED]);
        }
        session_write_close();
    }

    static private function sent2FARequest($variant, $target)
    {
        if ($variant == variants_2FA::email) {
            return API2FA::send_email($target);
        } elseif ($variant == variants_2FA::sms) {
            return API2FA::send_sms($target);
        }
        return FALSE;
    }
    
    static private function sent2FARequestByInvestor()
    {
        if (!Application::$authorizedInvestor) {
            return FALSE;
        }
        $preferred_2fa = Application::$authorizedInvestor->preferred_2fa;
        if (!in_array($preferred_2fa, API2FA::$allowedMethods)) {
            return FALSE;
        } elseif ($preferred_2fa == variants_2FA::email) {
            session_start();
            $_SESSION[self::TEMP_DATA_VARIANT] = variants_2FA::email;
            $_SESSION[self::TEMP_DATA_TARGET] = Application::$authorizedInvestor->email;
            session_write_close();
            return API2FA::send_email(Application::$authorizedInvestor->email);
        } elseif ($preferred_2fa == variants_2FA::sms) {
            session_start();
            $_SESSION[self::TEMP_DATA_VARIANT] = variants_2FA::sms;
            $_SESSION[self::TEMP_DATA_TARGET] = Application::$authorizedInvestor->phone;
            session_write_close();
            return API2FA::send_sms(Application::$authorizedInvestor->phone);
        }
        return FALSE;
    }
    
    static public function targetHide($string)
    {
        $length = mb_strlen($string);
        $startPoint = 2;
        $endPoint = $length - 3;
        $new_target = $string;
        for ($i = $startPoint; $i <= $endPoint; $i++) {
            $new_target[$i] = '*';
        }
        return $new_target;
    }
}