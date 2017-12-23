<?php

namespace core\controllers;

use core\engine\Application;
use core\engine\Email;
use core\engine\Utility;
use core\engine\DB;
use core\engine\Router;
use core\models\Investor;
use core\views\Base_view;
use core\views\Investor_view;
use core\views\Menu_point;

class Investor_controller
{
    static public $initialized = false;

    const BASE_URL = 'investor';
    const LOGIN_URL = 'investor/login';
    const SET_ETH_ADDRESS = 'investor/set_eth_address';
    const LOGOUT_URL = 'investor/logout';
    const REGISTER_URL = 'investor/register';
    const REGISTER_CONFIRMATION_URL = 'investor/register_confirm';
    const SETTINGS_URL = 'investor/settings';

    const SESSION_KEY = 'authorized_investor_id';
    const PREVIOUS_SYSTEM_ID = 'previous_system_authorized_investor_id';
    const PREVIOUS_SYSTEM_PASSWORD = 'previous_system_authorized_investor_password';

    static public function init()
    {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        self::detectLoggedInInvestor();

        Router::register(function () {
            if (Application::$authorizedInvestor) {
                Utility::location();
            } else {
                Utility::location(self::LOGIN_URL);
            }
        }, self::BASE_URL);

        Router::register(function () {
            self::handleEthSetForm();
        }, self::SET_ETH_ADDRESS, Router::GET_METHOD);
        Router::register(function () {
            self::handleEthSetPost();
        }, self::SET_ETH_ADDRESS, Router::POST_METHOD);

        Router::register(function () {
            if (Application::$authorizedInvestor) {
                Utility::location(self::BASE_URL);
            }
            Base_view::$TITLE = 'Login';
            Base_view::$MENU_POINT = Menu_point::Login;
            echo Base_view::header();
            echo Investor_view::loginForm();
            echo Base_view::footer();
        }, self::LOGIN_URL, Router::GET_METHOD);
        Router::register(function () {
            self::handleLoginRequest();
        }, self::LOGIN_URL, Router::POST_METHOD);

        Router::register(function () {
            if (!Application::$authorizedInvestor) {
                Utility::location(self::BASE_URL);
            }
            self::handleLogoutRequest();
        }, self::LOGOUT_URL);

        Router::register(function () {
            if (Application::$authorizedInvestor) {
                Utility::location(self::BASE_URL);
            }
            Base_view::$TITLE = 'Registration';
            Base_view::$MENU_POINT = Menu_point::Login;
            echo Base_view::header();
            echo Investor_view::registerForm();
            echo Base_view::footer();
        }, self::REGISTER_URL, Router::GET_METHOD);
        Router::register(function () {
            if (Application::$authorizedInvestor) {
                Utility::location(self::BASE_URL);
            }
            self::handleRegistrationRequest();
        }, self::REGISTER_URL, Router::POST_METHOD);
        Router::register(function () {
            self::handleRegistrationConfirmationRequest();
        }, self::REGISTER_CONFIRMATION_URL);

        Router::register(function () {
            self::handleSettingsRequest();
        }, self::SETTINGS_URL);
    }

    static private function detectLoggedInInvestor()
    {
        session_start();
        $authorizedInvestor = Investor::getById(@$_SESSION[self::SESSION_KEY]);
        if ($authorizedInvestor) {
            Application::$authorizedInvestor = $authorizedInvestor;
        }
        session_abort();
    }

    static private function loginWithId($investorId)
    {
        session_start();
        $_SESSION[self::SESSION_KEY] = $investorId;
        session_write_close();
    }

    static private function handleEthSetForm()
    {
        session_start();
        $investorId = 0;
        if (isset($_SESSION[self::PREVIOUS_SYSTEM_ID])) {
            $investorId = $_SESSION[self::PREVIOUS_SYSTEM_ID];
        }
        session_abort();
        if (!$investorId) {
            Utility::location(self::LOGIN_URL);
        }

        $investor = Investor::getById($investorId);
        if ($investor->eth_address) {
            Utility::location(self::LOGIN_URL);
        }

        Base_view::$TITLE = 'Set eth address';
        echo Base_view::header();
        echo Investor_view::ethSetupForm();
        echo Base_view::footer();
    }

    static private function handleEthSetPost()
    {
        session_start();
        $investorId = 0;
        if (isset($_SESSION[self::PREVIOUS_SYSTEM_ID]) && isset($_SESSION[self::PREVIOUS_SYSTEM_PASSWORD])) {
            $investorId = $_SESSION[self::PREVIOUS_SYSTEM_ID];
        } else {
            Utility::location(self::LOGIN_URL);
        }
        session_abort();

        if (!$investorId) {
            Utility::location(self::LOGIN_URL);
        }

        $investor = Investor::getById($investorId);
        if (is_null($investor)) {
            Utility::location(self::LOGIN_URL);
        }

        if (!Utility::validateEthAddress(@$_POST['eth_address'])) {
            Utility::location(self::SET_ETH_ADDRESS . '?err=1&err_text=not a valid eth address');
        }

        if ($investor->eth_address) {
            Utility::location(self::LOGIN_URL);
        }

        $investor->setEthAddress($_POST['eth_address']);
        if ($investor->tokens_count === 0 || Bounty_controller::mintTokens($investor, $investor->tokens_count) > 0) {
            session_start();
            $password = $_SESSION[self::PREVIOUS_SYSTEM_PASSWORD];
            if (isset($_SESSION[self::PREVIOUS_SYSTEM_ID])) {
                unset($_SESSION[self::PREVIOUS_SYSTEM_ID]);
                unset($_SESSION[self::PREVIOUS_SYSTEM_PASSWORD]);
            }
            session_write_close();
            $investor->changePassword($password);
            self::loginWithId($investorId);
        } else {
            $investor->setEthAddress('');
            Utility::location(self::SET_ETH_ADDRESS . '?err=8472&err_text=cant mint tokens right now');
        }

        Utility::location(self::BASE_URL);
    }

    static private function handleLoginRequest()
    {
        $investorId = @Investor::getInvestorIdByEmailPassword($_POST['email'], $_POST['password']);
        if ($investorId) {
            self::loginWithId($investorId);
            Utility::location(self::BASE_URL);
        } else {
            $investorId = Investor::investorId_previousSystemCredentials($_POST['email'], $_POST['password']);
            if ($investorId > 0) {
                session_start();
                $_SESSION[self::PREVIOUS_SYSTEM_ID] = $investorId;
                $_SESSION[self::PREVIOUS_SYSTEM_PASSWORD] = $_POST['password'];
                session_write_close();
                Utility::location(self::SET_ETH_ADDRESS);
            }
        }

        Utility::location(self::LOGIN_URL . '?err=3671&err_text=wrong credentials');
    }

    static private function handleLogoutRequest()
    {
        session_start();
        if (isset($_SESSION[self::SESSION_KEY])) {
            unset($_SESSION[self::SESSION_KEY]);
        }
        session_write_close();
        Utility::location();
    }

    static private function handleRegistrationRequest()
    {
        if (!filter_var(@$_POST['email'], FILTER_VALIDATE_EMAIL)) {
            Utility::location(self::REGISTER_URL . '?err=1&err_text=not a valid email');
        }
        if (!Utility::validateEthAddress(@$_POST['eth_address'])) {
            Utility::location(self::REGISTER_URL . '?err=2&err_text=not a valid eth address');
        }
        if (Investor::isExistWithParams($_POST['email'])) {
            Utility::location(self::REGISTER_URL . '?err=3&err_text=email already in use');
        }
        $referrerId = 0;
        if (@$_POST['referrer_code']) {
            $referrerId = Investor::getReferrerIdByCode(@$_POST['referrer_code']);
            if (!$referrerId) {
                Utility::location(self::REGISTER_URL . '?err=4&err_text=not a valid referrer code');
            }
        }
        if (!preg_match('/^[0-9A-Za-z?!@#$%\-\_\.,;:]{6,50}$/', @$_POST['password'])) {
            Utility::location(self::REGISTER_URL . '?err=5&err_text=not a valid password, use more than 6 characters');
        }
        $confirmationUrl = self::urlForRegistration($_POST['email'], $_POST['eth_address'], $referrerId, $_POST['password']);
        Email::send($_POST['email'], [], 'Cryptaur: email confirmation', "<a href=\"$confirmationUrl\">Confirm email to finish registration</a>");

        Base_view::$TITLE = 'Email confirmation info';
        Base_view::$MENU_POINT = Menu_point::Register;
        echo Base_view::header();
        echo Base_view::text("Please check your email and follow the sent link");
        echo Base_view::footer();
    }

    static private function handleRegistrationConfirmationRequest()
    {
        $data = @Utility::decodeData($_GET['d']);
        if (!$data) {
            Base_view::$TITLE = 'Email confirmation problem';
            Base_view::$MENU_POINT = Menu_point::Register;
            echo Base_view::header();
            echo Base_view::text('Perhaps the link is outdated');
            echo Base_view::footer();
            return;
        }
        $registerResult = Investor::registerUser($data['email'], $data['eth_address'], $data['referrer_id'], $data['password_hash']);
        if ($registerResult < 0) {
            Base_view::$TITLE = 'Email confirmation problem';
            Base_view::$MENU_POINT = Menu_point::Register;
            echo Base_view::header();
            echo Base_view::text("Something went wrong with investor registration ($registerResult)");
            echo Base_view::footer();
            return;
        }
        $investorId = $registerResult;

        self::loginWithId($investorId);
        self::detectLoggedInInvestor();

        Base_view::$TITLE = 'Email confirmed successfully';
        Base_view::$MENU_POINT = Menu_point::Register;
        echo Base_view::header();
        echo Base_view::text("Email confirmed successfully");
        echo Base_view::footer();
    }

    static private function handleSettingsRequest()
    {
        if (!Application::$authorizedInvestor) {
            Utility::location(self::BASE_URL);
        }

        Base_view::$TITLE = 'Settings';
        Base_view::$MENU_POINT = Menu_point::Settings;
        echo Base_view::header();
        echo Investor_view::settings();
        echo Base_view::footer();
    }

    static public function generateReferrerCode()
    {
        $referrerCode = null;
        do {
            $referrerCode = substr(uniqid(), -8);
        } while (DB::get("SELECT * FROM `investors` WHERE `referrer_code` = ? LIMIT 1;", [$referrerCode]));
        return $referrerCode;
    }

    static public function hashPassword($password)
    {
        return hash('sha256', $password . APPLICATION_ID . 'investor');
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