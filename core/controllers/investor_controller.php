<?php

namespace core\models;

use core\engine\Application;
use core\engine\Utility;
use core\engine\DB;
use core\engine\Router;
use core\views\Base_view;
use core\views\Investor_view;

class Investor_controller
{
    static public $initialized = false;

    const BASE_URL = 'investor';
    const LOGIN_URL = 'investor/login';
    const LOGOUT_URL = 'investor/logout';
    const REGISTER_URL = 'investor/register';
    const REGISTER_CONFIRMATION_URL = 'investor/register_confirm';

    static public function init()
    {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        session_start();
        $authorizedInvestor = Investor::getById($_SESSION['authorized_investor_id']);
        if ($authorizedInvestor) {
            Application::$authorizedInvestor = $authorizedInvestor;
        }
        session_abort();

        Router::register(function () {
            if (Application::$authorizedInvestor) {
                Utility::location();
            } else {
                Utility::location(self::LOGIN_URL);
            }
        }, self::BASE_URL);

        Router::register(function () {
            echo Base_view::header('Login');
            echo Investor_view::loginForm();
            echo Base_view::footer();
        }, self::LOGIN_URL, Router::GET_METHOD);
        Router::register(function () {
            self::handleLoginRequest();
        }, self::LOGIN_URL, Router::POST_METHOD);

        Router::register(function () {
            self::handleLogoutRequest();
        }, self::LOGOUT_URL);

        Router::register(function () {
            echo Base_view::header('Login');
            echo Investor_view::registerForm();
            echo Base_view::footer();
        }, self::REGISTER_URL, Router::GET_METHOD);
        Router::register(function () {
            self::handleRegistrationRequest();
        }, self::REGISTER_URL, Router::POST_METHOD);
        Router::register(function () {
            self::handleRegistrationConfirmationRequest();
        }, self::REGISTER_CONFIRMATION_URL);
    }

    static public function db_init()
    {
        DB::query("
            CREATE TABLE IF NOT EXISTS `investors` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `referrer_id` int(10) UNSIGNED NOT NULL,
                `referrer_code` varchar(32) NOT NULL,
                `joined_datetime` datetime(0) NOT NULL,
                `email` varchar(254) NOT NULL,
                `password_hash` varchar(254) NOT NULL,
                `eth_address` varchar(50) NOT NULL,
                `eth_withdrawn` bigint(20) NOT NULL,
                `tokens_count` bigint(20) UNSIGNED NOT NULL,
                PRIMARY KEY (`id`)
            );
        ");
    }

    static public function getInvestorIdByEmailPassword($email, $password)
    {
        $investor = DB::get("
            SELECT `id` FROM `investors`
            WHERE
                `email` = ? AND
                `password_hash` = ?
            LIMIT 1
        ;", [$email, self::hashPassword($password)]);
        if (!$investor) {
            return false;
        }
        return $investor[0]['id'];
    }

    /**
     * @param string $code
     * @return false|number
     */
    static public function getReferrerIdByCode($code)
    {
        $investorId = @DB::get("
            SELECT `id` FROM `investors`
            WHERE
                `referrer_code` = ?
            LIMIT 1
        ;", [$code])[0]['id'];
        if (!$investorId) {
            return false;
        }
        return (int)$investorId;
    }

    static public function handleLoginRequest()
    {
        $investorId = @self::getInvestorIdByEmailPassword($_POST['email'], $_POST['password']);
        if ($investorId) {
            session_start();
            $_SESSION['authorized_investor_id'] = $investorId;
            session_write_close();
            Utility::location(self::BASE_URL);
        }
        Utility::location(self::LOGIN_URL);
    }

    static public function handleLogoutRequest()
    {
        session_start();
        unset($_SESSION['authorized_investor_id']);
        session_write_close();
        Utility::location(self::BASE_URL);
    }

    static public function handleRegistrationRequest()
    {
        if (!filter_var(@$_POST['email'], FILTER_VALIDATE_EMAIL)) {
            Utility::location(self::REGISTER_URL . '?err=1');
        }
        if (!Utility::validateEthAddress(@$_POST['eth_address'])) {
            Utility::location(self::REGISTER_URL . '?err=2');
        }
        if (self::isExistInvestorWithParams($_POST['email'], $_POST['eth_address'])) {
            Utility::location(self::REGISTER_URL . '?err=3');
        }
        $referrerId = 0;
        if (@$_POST['referrer_code']) {
            $referrerId = self::getReferrerIdByCode(@$_POST['referrer_code']);
            if (!$referrerId) {
                Utility::location(self::REGISTER_URL . '?err=4');
            }
        }
        if (!preg_match('/^[0-9A-Za-z?!@#$%\-\_\.,;:]{6,50}$/', @$_POST['password'])) {
            Utility::location(self::REGISTER_URL . '?err=5');
        }
        echo self::urlForRegistration($_POST['email'], $_POST['eth_address'], $referrerId, $_POST['password']);
    }

    static public function handleRegistrationConfirmationRequest()
    {
        $data = @Utility::decodeData($_GET['d']);
        if (!$data) {
            echo 'Perhaps the link is outdated';
            return;
        }
        $investorId = self::registerUser($data['email'], $data['eth_address'], $data['referrer_id'], $data['password_hash']);
        if (!$investorId) {
            echo 'Sorry, something went wrong with investor registration';
            return;
        }
        echo "Registered $investorId!";
    }

    static public function isExistInvestorWithParams($email, $eth_address)
    {
        return !!@DB::get("
            SELECT * FROM `investors`
            WHERE
                `email` = ? OR
                `eth_address` = ?
            LIMIT 1
        ;", [$email, $eth_address])[0];
    }

    /**
     * @param string $email
     * @param string $eth_address
     * @param int $referrer_id
     * @param string $password_hash
     * @return false|int
     */
    static public function registerUser($email, $eth_address, $referrer_id, $password_hash)
    {
        if (!Utility::validateEthAddress($eth_address)) {
            return false;
        }

        if (self::isExistInvestorWithParams($email, $eth_address)) {
            return false;
        }

        if ($referrer_id) {
            $existingReferrer = @DB::get("
                SELECT * FROM `investors`
                WHERE
                    `id` = ?
                LIMIT 1
            ;", [$referrer_id])[0];
            if (!$existingReferrer) {
                return false;
            }
        }

        $referrer_code = self::generateReferrerCode();

        DB::set("
            INSERT INTO `investors`
            SET
                `referrer_id` = ?,
                `referrer_code` = ?,
                `joined_datetime` = ?,
                `email` = ?,
                `password_hash` = ?,
                `eth_address` = ?,
                `eth_withdrawn` = ?,
                `tokens_count` = ?
            ", [$referrer_id, $referrer_code, DB::timetostr(time()), $email, $password_hash, $eth_address, 0, 0]
        );

        return DB::lastInsertId();
    }

    static private function generateReferrerCode()
    {
        $referrerCode = null;
        do {
            $referrerCode = substr(uniqid(), -9);
        } while (DB::get("SELECT * FROM `investors` WHERE `referrer_code` = ? LIMIT 1;", [$referrerCode]));
        return $referrerCode;
    }

    static public function hashPassword($password)
    {
        return hash('sha256', $password . APPLICATION_ID);
    }

    static public function urlForRegistration($email, $eth_address, $referrer_id, $password)
    {
        $data = [
            'email' => $email,
            'eth_address' => $eth_address,
            'referrer_id' => $referrer_id,
            'password_hash' => self::hashPassword($password)
        ];
        return APPLICATION_URL . '/' . self::REGISTER_CONFIRMATION_URL . '?d=' . Utility::encodeData($data);
    }
}