<?php

namespace core\secondfactor;

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
    const SECRET_KEY_SMS = 'secret_key_sms';
    const SECRET_KEY_EMAIL = 'secret_key_email';
    const SECRET_KEY = 'secret_key';
    const SMS_SERVICE_URL = 'https://liketelecom.net/sms014/mc-sms-send.php?';
    const SMS_FROM = 'Cryptaur';

    private function __construct()
    {
    }

    /**
     * singleton object
     * @var GAuthify
     */
    static private $_instance;

    /**
     * @return API2FA
     */
    static private function inst()
    {
        if (is_null(self::$_instance)) {
            self::initializeErrorFile();
            self::$_instance = new self();
            self::$_instance->init();
        }
        return self::$_instance;
    }
    
    /**
     * init
     */
    private function init()
    {
        
    }
    
    /*
     * Checks authcode returns true/false depending on correctness
     */
    public static function check($code)
    {
        if (!isset($_SESSION[self::SECRET_KEY])) {
            return FALSE;
        }
        $code_stored = $_SESSION[self::SECRET_KEY];
        session_start();
        unset($_SESSION[self::SECRET_KEY]);
        session_write_close();
        if ($code != $code_stored) {
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
        ) {
            return FALSE;
        }
        $code_stored_1 = $_SESSION[self::SECRET_KEY_SMS];
        $code_stored_2 = $_SESSION[self::SECRET_KEY_EMAIL];
        session_start();
        unset($_SESSION[self::SECRET_KEY_SMS]);
        unset($_SESSION[self::SECRET_KEY_EMAIL]);
        session_write_close();
        if ($code_1 == $code_stored_1 && $code_2 == $code_stored_2) {
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
        session_write_close();
        //send SMS
        $result = self::raw_sms_sender($phone, $code_1);
        if ($result == FALSE) {
            return FALSE;
        }
        //an now email
        return \core\engine\Email::send($email, [], 'Cryptaur: secret code', "<p>Secret code:</p><p>$code_2</p>", true);
        
    }
    
    /*
     * Sends email with the one time auth_code
     */
    public static function send_email($email)
    {
        $captcha = self::generate_code();
        session_start();
        $_SESSION[self::SECRET_KEY] = $captcha;
        session_write_close();
        return \core\engine\Email::send($email, [], 'Cryptaur: secret code', "<p>Secret code:</p><p>$captcha</p>", true);
    }
    
    /*
     * Sends text message to phone number with the one time auth_code
     */
    public static function send_sms($phone)
    {
        $captcha = self::generate_code();
        session_start();
        $_SESSION[self::SECRET_KEY] = $captcha;
        session_write_close();
        return self::raw_sms_sender($phone, $captcha);
    }
    
    private static function raw_sms_sender($phone, $message)
    {
        $url = self::SMS_SERVICE_URL . 
            'sender=' . self::SMS_FROM . 
            '&recipient=' . $phone . 
            '&message=' . $message;
        $response = file_get_contents($url);
        $json = json_decode($response);
        if (!$json || !isset($json['message']) || $json['message'] != 'OK') {
            return FALSE;
        }
        return TRUE;
    }
            

    private static function generate_code()
    {
        $letters = 'ABCDEFGKIJKLMNOPQRSTUVWXYZ0123456789';
        $caplen = 6;
        $captcha = '';
        for ($i = 0; $i < $caplen; $i++) {
            $captcha .= $letters[rand(0, strlen($letters) - 1)]; // дописываем случайный символ из алфавила
        }
        return $captcha;
    }
}