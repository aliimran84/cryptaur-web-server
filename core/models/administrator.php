<?php

namespace core\models;

use core\engine\DB;
use core\controllers\Administrator_controller;

class Administrator
{
    public $id = 0;
    public $email = '';

    static public function db_init()
    {
        DB::query("
            CREATE TABLE IF NOT EXISTS `administrators` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `email` varchar(254) NOT NULL,
                `password_hash` varchar(254) NOT NULL,
                PRIMARY KEY (`id`)
            );
        ");
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
}