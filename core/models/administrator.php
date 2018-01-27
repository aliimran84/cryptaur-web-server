<?php

namespace core\models;

use core\engine\DB;
use core\controllers\Administrator_controller;

class Administrator
{
    public $id = 0;
    public $email = '';
    static private $LOG_PHP = 'php-errors.log';
    static private $LOG_MYSQL = 'mysqli-errors.log';

    const linesToGet = 500;

    static public function db_init()
    {
        DB::query("
            CREATE TABLE IF NOT EXISTS `administrators` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `email` varchar(254) NOT NULL,
                `password_hash` varchar(254) NOT NULL,
                PRIMARY KEY (`id`)
            )
            DEFAULT CHARSET utf8
            DEFAULT COLLATE utf8_general_ci
        ;");
        DB::query("
            CREATE TABLE IF NOT EXISTS `alarm_messages` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `message` text NULL,
                PRIMARY KEY (`id`)
            )
            DEFAULT CHARSET utf8
            DEFAULT COLLATE utf8_general_ci
        ;");
    }

    /**
     * @param string $email
     * @param string $password_hash
     */
    static public function setAdministrator($email, $password_hash)
    {
        if (Administrator::isExistWithParams($email)) {
            DB::set("
                UPDATE `administrators`
                SET
                    `password_hash` = ?
                WHERE
                    `email` = ?
                ", [$password_hash, $email]
            );
        } else {
            DB::set("
                INSERT INTO `administrators`
                SET
                    `email` = ?,
                    `password_hash` = ?
                ", [$email, $password_hash]
            );
        }
    }

    static public function deleteAlarmMessage($id)
    {
        DB::query("
            DELETE FROM `alarm_messages` 
            WHERE `id` = " . $id . "
        ");
    }

    static public function setAlarmMessage($message)
    {
        $alarm_messages = DB::set("
            INSERT INTO `alarm_messages`
            SET
                `message` = ?
            ", [$message]
        );

        return $alarm_messages;
    }

    /**
     * @param int $limit
     * @return array
     */
    static public function getAlarmMessage($limit)
    {
        $alarm_messages = DB::get("
            SELECT *
            FROM `alarm_messages`
            LIMIT ?
        ", [$limit]);

        return $alarm_messages;
    }

    static public function getById($id)
    {
        $administrator = @DB::get("
            SELECT * FROM `administrators`
            WHERE
                `id` = ?
            LIMIT 1
        ;", [$id])[0];

        if (!$administrator) {
            return null;
        }

        $instance = new Administrator();
        $instance->id = $administrator['id'];
        $instance->email = $administrator['email'];

        return $instance;
    }

    static public function getIdByEmailPassword($email, $password)
    {
        $administrator = DB::get("
            SELECT `id` FROM `administrators`
            WHERE
                `email` = ? AND
                `password_hash` = ?
            LIMIT 1
        ;", [$email, Administrator_controller::hashPassword($password)]);
        if (!$administrator) {
            return false;
        }
        return $administrator[0]['id'];
    }

    static public function isExistWithParams($email)
    {
        return !!@DB::get("
            SELECT * FROM `administrators`
            WHERE
                `email` = ?
            LIMIT 1
        ;", [$email])[0];
    }

    /**
     * @return Administrator[]
     */
    static public function getAll()
    {
        $all = [];
        $allIds = @DB::get("SELECT `id` FROM `administrators`;");
        foreach ($allIds as $arrWithId) {
            $all[] = self::getById($arrWithId['id']);
        }
        return $all;
    }

    /**
     * @return string[] dataPHP
     */
    static public function getLogsPHP()
    {
        $dataPHP = [];
        $path = PATH_TO_TMP_DIR . '/' . self::$LOG_PHP;
        $lines = self::linesToGet;
        exec("tail -n $lines $path", $dataPHP);
        return $dataPHP;
    }

    /**
     * @return string[] dataMySQL
     */
    static public function getLogsMySQL()
    {
        $dataMySQL = [];
        $path = PATH_TO_TMP_DIR . '/' . self::$LOG_MYSQL;
        $lines = self::linesToGet;
        exec("tail -n $lines $path", $dataMySQL);
        return $dataMySQL;
    }
}