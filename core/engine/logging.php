<?php

namespace core\logging;

use core\controllers\Investor_controller;
use core\controllers\Administrator_controller;
use core\engine\Application;
use core\engine\DB;

class ActionList
{
    const LOGIN_TRY = 'login_try';
    const LOGIN_FAIL = 'login_fail';
    const LOGIN_SUCCESS = 'login_success';
    const REGISTRATION_TRY = 'registration_try';
    const REGISTRATION_FAIL = 'registration_fail';
    const REGISTRATION_SUCCESS = 'registration_succes';
}

class Log
{
    public static function db_init()
    {
        DB::query("
            CREATE TABLE IF NOT EXISTS `log` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `context` varchar(50) NOT NULL,
                `ip` varchar(500) NOT NULL,
                `action_based_user_id` int(10) UNSIGNED NULL,
                `action_type` varchar(50) NOT NULL,
                `action_based_id` int(10) UNSIGNED NULL,
                `text` text NULL,
                `datetime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            )
            DEFAULT CHARSET utf8
            DEFAULT COLLATE utf8_general_ci
        ;");
    }
    
    public static function getIp()
    {
        $ip = NULL;
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public static function base($context, $action_based_user_id, $action_type, $action_based_id=NULL, $ip=NULL, $log=NULL)
    {
        DB::set("
            INSERT INTO `log` 
            (`context`, `ip`, `action_based_user_id`, `action_type`, `action_based_id`, `text`) 
            VALUES 
            (?, ?, ?, ?, ?, ?)
        ;", [$context, $ip, $action_based_user_id, $action_type, $action_based_id, $log]);
    }
    
    public static function investor($action_type, $action_based_id=NULL, $log=NULL)
    {
        $action_based_user_id = NULL;
        $context = 'unauthorized';
        $ip = self::getIp();

        if (isset($_SESSION[Investor_controller::SESSION_KEY])) {
            $context = 'investor';
            $action_based_user_id = $_SESSION[Investor_controller::SESSION_KEY];
        }

        self::base($context, $action_based_user_id, $action_type, $action_based_id, $ip, $log);
    }
    
    public static function administrator($action_type, $action_based_id=NULL, $log=NULL)
    {
        $action_based_user_id = NULL;
        $context = 'unauthorized';
        $ip = self::getIp();

        if (isset($_SESSION[Administrator_controller::SESSION_KEY])) {
            $context = 'administrator';
            $action_based_user_id = $_SESSION[Administrator_controller::SESSION_KEY];
        }

        self::base($context, $action_based_user_id, $action_type, $action_based_id, $ip, $log);
    }
    
    public static function actionCountByIP($ip, $action_type, $unixtimeFrom)
    {
        $timeFrom = date('Y-m-d H:i:s', $unixtimeFrom);
        $count = @DB::get("
            SELECT COUNT(*) AS `CNT` FROM `log`
            WHERE `action_type` = ? 
            AND `ip` = ? 
            AND `datetime` >= ? 
            AND `datetime` <= NOW() 
            LIMIT 1
        ;", [$action_type, $ip, $timeFrom])[0]['CNT'];
        return $count;
    }
}