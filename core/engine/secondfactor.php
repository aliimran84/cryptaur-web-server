<?php

namespace core\secondfactor;

use core\engine\Email;
use core\engine\Utility;

class variants_2FA
{
    const none = 'NONE';
    const email = 'EMAIL';
    const sms = 'SMS';
    const both = 'SMS&EMAIL';

    /**
     * @return array
     */
    static public function varList()
    {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }
}

class API2FA
{
    static public $initialized = false;
    
    const SECRET_KEY_SMS = 'secret_key_sms';
    const SECRET_KEY_EMAIL = 'secret_key_email';
    const SECRET_KEY = 'secret_key';
    const SECRET_COUNT = 'secret_count';
    const TRY_TIMES = 5;
    const SMS_FROM = 'Cryptaur';
    const CODE_LENGTH = 4;
    const CODE_LETTERS = '123456789';
    
    static public $allowedMethods = [];

    static public function init()
    {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;
        
        if (USE_2FA === TRUE) {
            $list2FA = variants_2FA::varList();
            $usedCnt = 0;
            foreach ($list2FA AS $var) {
                if (constant($var . '_2FA') === TRUE) {
                    $usedCnt++;
                    self::$allowedMethods[] = $var;
                }
            }
            if ($usedCnt == 0) {
                echo "---Stop on init API2FA---<br><br>\n\n";
                echo "No 2FA methods selected while 2FA being turned on!";
                exit;
            }
        }
    }

    /*
     * Checks authcode returns true/false depending on correctness
     */
    public static function check($code)
    {
        if (
            !isset($_SESSION[self::SECRET_KEY])
            || !isset($_SESSION[self::SECRET_COUNT])
        ) {
            return NULL;
        }
        $code_stored = $_SESSION[self::SECRET_KEY];
        if ($code != $code_stored) {
            if ($_SESSION[self::SECRET_COUNT] > 0) {
                session_start();
                $_SESSION[self::SECRET_COUNT]--;
                session_write_close();
            } else {
                session_start();
                unset($_SESSION[self::SECRET_KEY]);
                unset($_SESSION[self::SECRET_COUNT]);
                session_write_close();
                return NULL;
            }
            return FALSE;
        }
        return TRUE;
    }

    /*
     * Checks authcode returns true/false depending on correctness
     */
    public static function check_both($code_1, $code_2)
    {
        if (
            !isset($_SESSION[self::SECRET_KEY_SMS])
            || !isset($_SESSION[self::SECRET_KEY_EMAIL])
            || !isset($_SESSION[self::SECRET_COUNT])
        ) {
            return NULL;
        }
        $code_stored_1 = $_SESSION[self::SECRET_KEY_SMS];
        $code_stored_2 = $_SESSION[self::SECRET_KEY_EMAIL];
        if ($code_1 == $code_stored_1 && $code_2 == $code_stored_2) {
            if ($_SESSION[self::SECRET_COUNT] > 0) {
                session_start();
                $_SESSION[self::SECRET_COUNT]--;
                session_write_close();
            } else {
                session_start();
                unset($_SESSION[self::SECRET_KEY_SMS]);
                unset($_SESSION[self::SECRET_KEY_EMAIL]);
                unset($_SESSION[self::SECRET_COUNT]);
                session_write_close();
                return NULL;
            }
            return TRUE;
        }
        return FALSE;
    }

    /*
     * Sends email with the one time auth_code
     */
    public static function send_both($email, $phone)
    {
        $code_1 = self::generate_code();
        $code_2 = self::generate_code();
        session_start();
        $_SESSION[self::SECRET_KEY_SMS] = $code_1;
        $_SESSION[self::SECRET_KEY_EMAIL] = $code_2;
        $_SESSION[self::SECRET_COUNT] = self::TRY_TIMES;
        session_write_close();
        //send SMS
        $result = self::raw_sms_sender($phone, $code_1);
        if ($result == FALSE) {
            return FALSE;
        }
        //an now email
        return Email::send($email, [], 'Cryptaur: secret code', "<p>Secret code:</p><p>$code_2</p>", true, false);

    }

    /*
     * Sends email with the one time auth_code
     */
    public static function send_email($email)
    {
        $captcha = self::generate_code();
        session_start();
        $_SESSION[self::SECRET_KEY] = $captcha;
        $_SESSION[self::SECRET_COUNT] = self::TRY_TIMES;
        session_write_close();
        return Email::send($email, [], 'Cryptaur: secret code', "<p>Secret code:</p><p>$captcha</p>", true, false);
    }

    /*
     * Sends text message to phone number with the one time auth_code
     */
    public static function send_sms($phone)
    {
        Utility::log('send_sms/' . Utility::microtime_float(), [
            'phone' => $phone
        ]);
        $phone = preg_replace('/[^0-9]/', '', $phone);
        $captcha = self::generate_code();
        session_start();
        $_SESSION[self::SECRET_KEY] = $captcha;
        $_SESSION[self::SECRET_COUNT] = self::TRY_TIMES;
        session_write_close();
        return self::raw_sms_sender($phone, $captcha);
    }

    private static function raw_sms_sender($phone, $message)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        $url = SMS_URL .
            '?sender=' . self::SMS_FROM .
            '&recipient=' . $phone .
            '&message=' . $message;
        $response = file_get_contents($url);
        Utility::log('raw_send_sms/' . Utility::microtime_float(), [
            'phone' => $phone,
            'response' => $response
        ]);
        $json = json_decode($response, TRUE);
        if (!$json || !isset($json['message']) || $json['message'] != 'OK') {
            return FALSE;
        }
        return TRUE;
    }

    private static function generate_code()
    {
        $captcha = '';
        $length = strlen(self::CODE_LETTERS);
        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $captcha .= self::CODE_LETTERS[rand(0, $length - 1)]; // дописываем случайный символ из алфавила
        }
        return $captcha;
    }
}