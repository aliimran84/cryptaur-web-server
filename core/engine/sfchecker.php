<?php

namespace core\sfchecker;

use core\engine\Utility;
use core\engine\Router;
use core\engine\Application;
use core\translate\Translate;
use core\secondfactor\API2FA;
use core\secondfactor\variants_2FA;
use core\controllers\Investor_controller;
use core\views\SFchecker_view;
use core\views\Base_view;

class ACTION2FA
{
    const TEMP_DATA_ARR = 'temp_data_arr';
    const TEMP_DATA_URL = 'temp_data_url';
    const TEMP_DATA_METHOD = 'temp_data_method';
    const TEMP_DATA_VARIANT = 'temp_data_variant';
    const TEMP_FORM_TYPE = 'temp_data_form_type';
    const TEMP_DATA_TARGET_1 = 'temp_data_target_1';
    const TEMP_DATA_TARGET_2 = 'temp_data_target_2';
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

    static private function action2FAVerify()
    {
        $sfa_form = Investor_controller::investor2FAFormType();
        if (
            $sfa_form === 0 || (
                isset($_SESSION[self::TEMP_DATA_VARIANT]) && (
                    $_SESSION[self::TEMP_DATA_VARIANT] == variants_2FA::email
                    || $_SESSION[self::TEMP_DATA_VARIANT] == variants_2FA::sms
                )
            )
        ) {
            session_start();
            $_SESSION[self::TEMP_FORM_TYPE] = 0;
            session_write_close();
            self::formDraw(0);
        } elseif (
            $sfa_form === 1 || (
                isset($_SESSION[self::TEMP_DATA_VARIANT])
                && $_SESSION[self::TEMP_DATA_VARIANT] == variants_2FA::both
            )
        ) {
            session_start();
            $_SESSION[self::TEMP_FORM_TYPE] = 1;
            session_write_close();
            self::formDraw(1);
        }
    }
    
    static private function formDraw($form_type, $message = '')
    {
        echo Base_view::header();
        if ($form_type === 0) {
            echo SFchecker_view::secondfactorForm($_SESSION[self::TEMP_DATA_URL], $_SESSION[self::TEMP_DATA_METHOD], $message);
        } elseif ($form_type === 1) {
            echo SFchecker_view::secondfactorDualForm($_SESSION[self::TEMP_DATA_URL], $_SESSION[self::TEMP_DATA_METHOD], $message);
        }
        echo Base_view::footer();
        exit;
    }

    static public function access2FAChecker($url, $method, $variant = NULL, $target_1 = NULL, $target_2 = NULL)
    {
        if (
            (isset($_SESSION[self::LAST_SECURED_TIME]) && time() - $_SESSION[self::LAST_SECURED_TIME] < SECURED_SESSION_TIME)
            || USE_2FA == FALSE
        ) {
            return NULL;
        } else {
            //if 2FA must be used
            if (!isset($_SESSION[self::TEMP_DATA_URL]) || !isset($_SESSION[self::TEMP_DATA_METHOD])) {
                //first-time 2FA init
                self::clearSessionData(TRUE); //clean session data to make process clear
                session_start();
                $_SESSION[self::TEMP_DATA_URL] = $url;
                $_SESSION[self::TEMP_DATA_METHOD] = $method;
                if (isset($variant) && isset($target_1)) {
                    $_SESSION[self::TEMP_DATA_VARIANT] = $variant;
                    $_SESSION[self::TEMP_DATA_TARGET_1] = $target_1;
                    if (isset($target_2)) {
                        $_SESSION[self::TEMP_DATA_TARGET_2] = $target_2;
                    }
                }
                session_write_close();
                self::readFromPost();
                self::action2FAVerify();
            } elseif (isset($_SESSION[self::TEMP_DATA_URL]) && isset($_SESSION[self::TEMP_DATA_METHOD])) {
                if ($_SESSION[self::TEMP_DATA_URL] != $url) {
                    //if there was unfinised check
                    self::clearSessionData(TRUE); //clean session data to make process clear
                    self::access2FAChecker($url, $method, $variant, $target_1, $target_2);
                } elseif (!isset($_POST['commit'])) {
                    //if user trying sent/resent the code(s)
                    $message = "";
                    $time = time();
                    if (isset($_SESSION[self::LAST_2FA_TRY]) && $time - $_SESSION[self::LAST_2FA_TRY] < CODE_SENT_TIME) {
                        $diff = CODE_SENT_TIME - ($time - $_SESSION[self::LAST_2FA_TRY]);
                        $message = Translate::td('You cannot send another code(s) until seconds will expire', ['num' => $diff]);
                        self::formDraw($_SESSION[self::TEMP_FORM_TYPE], $message);
                    } else {
                        session_start();
                        $_SESSION[self::LAST_2FA_TRY] = $time;
                        $_SESSION[self::TEMP_DATA_SENDED] = 1;
                        session_write_close();
                        $sended;
                        if (
                            isset($_SESSION[self::TEMP_DATA_VARIANT])
                            || isset($_SESSION[self::TEMP_DATA_TARGET_1])
                            || isset($_SESSION[self::TEMP_DATA_TARGET_2])
                        ) {
                            $sended = self::sent2FARequest(
                                $_SESSION[self::TEMP_DATA_VARIANT], 
                                $_SESSION[self::TEMP_DATA_TARGET_1], 
                                $_SESSION[self::TEMP_DATA_TARGET_2]
                            );
                        } elseif (
                            isset($_SESSION[self::TEMP_DATA_VARIANT])
                            || isset($_SESSION[self::TEMP_DATA_TARGET_1])
                        ) {
                            $sended = self::sent2FARequest(
                                $_SESSION[self::TEMP_DATA_VARIANT], 
                                $_SESSION[self::TEMP_DATA_TARGET_1]
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
                        self::formDraw($_SESSION[self::TEMP_FORM_TYPE], $message);
                    }
                } elseif (
                    isset($_POST['commit']) && (
                        isset($_POST['otp']) || (isset($_POST['code_1']) && isset($_POST['code_2']))
                )) {
                    $checked;
                    if (isset($_POST['otp'])) {
                        //case for single code
                        $checked = API2FA::check($_POST['otp']);
                    } else {
                        //case for dual code
                        $checked = API2FA::check_both($_POST['code_1'], $_POST['code_2']);
                    }
                    session_start();
                    unset($_SESSION[self::TEMP_DATA_SENDED]);
                    session_write_close();
                    if ($checked === TRUE) {
                        ACTION2FA::smart2FARedirect();
                    } else {
                        $message = Translate::td('wrong authentication code');
                        self::formDraw($_SESSION[self::TEMP_FORM_TYPE], $message);
                    }
                } else {
                    self::formDraw($_SESSION[self::TEMP_FORM_TYPE]);
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
            self::clearSessionData(TRUE);
            call_user_func(Router::getByPathAndMethod($url, $method));
            exit;
        }
    }
    
    static public function clearSessionData($try_clear = FALSE)
    {
        session_start();
        if (isset($_SESSION[self::TEMP_DATA_URL])) {
            unset($_SESSION[self::TEMP_DATA_URL]);
        }
        if (isset($_SESSION[self::TEMP_DATA_METHOD])) {
            unset($_SESSION[self::TEMP_DATA_METHOD]);
        }
        if (isset($_SESSION[self::TEMP_FORM_TYPE])) {
            unset($_SESSION[self::TEMP_FORM_TYPE]);
        }
        if (isset($_SESSION[self::LAST_2FA_TRY])) {
            unset($_SESSION[self::LAST_2FA_TRY]);
        }
        if (isset($_SESSION[self::TEMP_DATA_VARIANT])) {
            unset($_SESSION[self::TEMP_DATA_VARIANT]);
        }
        if (isset($_SESSION[self::TEMP_DATA_TARGET_1])) {
            unset($_SESSION[self::TEMP_DATA_TARGET_1]);
        }
        if (isset($_SESSION[self::TEMP_DATA_TARGET_2])) {
            unset($_SESSION[self::TEMP_DATA_TARGET_2]);
        }
        if (isset($_SESSION[self::TEMP_DATA_ARR])) {
            unset($_SESSION[self::TEMP_DATA_ARR]);
        }
        if (isset($_SESSION[self::TEMP_DATA_SENDED])) {
            unset($_SESSION[self::TEMP_DATA_SENDED]);
        }
        session_write_close();
    }

    static private function sent2FARequest($variant, $target_1, $target_2 = NULL)
    {
        if ($variant == variants_2FA::email) {
            return API2FA::send_email($target_1);
        } elseif ($variant == variants_2FA::sms) {
            return API2FA::send_sms($target_1);
        } elseif ($variant == variants_2FA::both) {
            return API2FA::send_both($target_1, $target_2);
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
            return API2FA::send_email(Application::$authorizedInvestor->email);
        } elseif ($preferred_2fa == variants_2FA::sms) {
            return API2FA::send_sms(Application::$authorizedInvestor->phone);
        } elseif ($preferred_2fa == variants_2FA::both) {
            return API2FA::send_both(Application::$authorizedInvestor->email, Application::$authorizedInvestor->phone);
        }
        return FALSE;
    }
}