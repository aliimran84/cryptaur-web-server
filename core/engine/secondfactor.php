<?php

namespace core\secondfactor;

use core\engine\Email;
use core\engine\Utility;

class variants_2FA
{
    const none = 'NONE';
    const email = 'EMAIL';
    const sms = 'SMS';

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