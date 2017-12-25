<?php

namespace core\controllers;

use core\engine\Application;
use core\engine\Email;
use core\engine\Utility;
use core\engine\DB;
use core\engine\Router;
use core\models\Investor;
use core\translate\Translate;
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
    const RECOVER_URL = 'investor/recover';
    const CHANGE_PASSWORD_URL = 'investor/changepassword';
    const REGISTER_URL = 'investor/register';
    const PREVIOUS_SYSTEM_REGISTER_URL = 'syndicates/join';
    const REGISTER_CONFIRMATION_URL = 'investor/register_confirm';
    const SETTINGS_URL = 'investor/settings';
    const INVITE_FRIENDS_URL = 'investor/invite_friends';

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
            Utility::location(self::REGISTER_URL . '?referrer_code=' . Router::$queryVar);
        }, self::PREVIOUS_SYSTEM_REGISTER_URL, Router::ANY_METHOD);

        Router::register(function () {
            if (Application::$authorizedInvestor) {
                Utility::location(self::BASE_URL);
            }
            self::handleRegistrationRequest();
        }, self::REGISTER_URL, Router::POST_METHOD);

        Router::register(function () {
            self::handleRecoverForm();
        }, self::RECOVER_URL, Router::GET_METHOD);

        Router::register(function () {
            self::handleRecoverRequest();
        }, self::RECOVER_URL, Router::POST_METHOD);

        Router::register(function () {
            self::handleChangePassword();
        }, self::CHANGE_PASSWORD_URL, Router::GET_METHOD);

        Router::register(function () {
            self::handleRegistrationConfirmationRequest();
        }, self::REGISTER_CONFIRMATION_URL);

        Router::register(function () {
            self::handleSettingsForm();
        }, self::SETTINGS_URL, Router::GET_METHOD);

        Router::register(function () {
            self::handleSettingsRequest();
        }, self::SETTINGS_URL, Router::POST_METHOD);

        Router::register(function () {
            self::handleInviteFriendsForm();
        }, self::INVITE_FRIENDS_URL, Router::GET_METHOD);

        Router::register(function () {
            self::handleInviteFriendsRequest();
        }, self::INVITE_FRIENDS_URL, Router::POST_METHOD);
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

    /**
     * @param string $password
     * @return bool
     */
    static private function verifyPassword($password)
    {
        return !!preg_match('/^[0-9A-Za-z?!@#$%\-\_\.,;:]{6,50}$/', $password);
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
        if (!self::verifyPassword(@$_POST['password'])) {
            Utility::location(self::REGISTER_URL . '?err=5&err_text=not a valid password, use more than 6 characters');
        }
        $confirmationUrl = self::urlForRegistration($_POST['email'], @$_POST['firstname'], @$_POST['lastname'], $_POST['eth_address'], $referrerId, $_POST['password']);
        Email::send($_POST['email'], [], 'Cryptaur: email confirmation', "<a href=\"$confirmationUrl\">Confirm email to finish registration</a>");

        Base_view::$TITLE = 'Email confirmation info';
        Base_view::$MENU_POINT = Menu_point::Register;
        echo Base_view::header();
        echo Base_view::text(Translate::td("Please check your email and follow the sent link"));
        echo Base_view::footer();
    }

    static private function handleRecoverForm($message = '')
    {
        if (Application::$authorizedInvestor) {
            Utility::location(self::BASE_URL);
        }
        Base_view::$TITLE = Translate::td('Recover');
        Base_view::$MENU_POINT = Menu_point::Login;
        echo Base_view::header();
        echo Investor_view::recoverForm($message);
        echo Base_view::footer();
    }

    static private function handleRecoverRequest()
    {
        if (Application::$authorizedInvestor) {
            Utility::location(self::BASE_URL);
        }
        if (!filter_var(@$_POST['email'], FILTER_VALIDATE_EMAIL)) {
            Utility::location(self::RECOVER_URL . '?err=1&err_text=not a valid email');
        }
        $investor = Investor::getByEmail(@$_POST['email']);
        if ($investor) {
            $password = substr(uniqid(), -6);
            $data = [
                'password' => $password,
                'email' => $_POST['email'],
                'time' => time()
            ];
            $url = APPLICATION_URL . '/' . self::CHANGE_PASSWORD_URL . '?d=' . Utility::encodeData($data);;
            $html = <<<EOT
<h3>Forgot password</h3>
<p>Please follow the <a href="$url">link</a> to change password to <strong>$password</strong>:</p>
<p><a href="$url">$url</a></p>
<p>Link will be working for 48 hours.</p>
EOT;
            Email::send($investor->email, [], Translate::td('Forgot password'), $html, true);
        }
        self::handleRecoverForm(Translate::td('If the user exists then he was sent a new password'));
    }

    static private function handleChangePassword()
    {
        if (Application::$authorizedInvestor) {
            Utility::location(self::BASE_URL);
        }
        $encodedData = @$_GET['d'];
        if (!$encodedData) {
            Utility::location(self::RECOVER_URL . '?err=1&err_text=something went wrong');
        }
        $data = Utility::decodeData($encodedData);
        if (!$data) {
            Utility::location(self::RECOVER_URL . '?err=2&err_text=something went wrong');
        }
        $investor = Investor::getByEmail(@$data['email']);
        if (!$investor) {
            Utility::location(self::RECOVER_URL . '?err=3&err_text=something went wrong');
        }
        if (time() - 48 * 60 * 60 > $data['time']) {
            Utility::location(self::RECOVER_URL . '?err=4&err_text=link is outdated');
        }
        $investor->changePassword($data['password']);
        self::handleRecoverForm(Translate::td('Password successfully chagned'));
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
        $registerResult = Investor::registerUser($data['email'], $data['firstname'], $data['lastname'], $data['eth_address'], $data['referrer_id'], $data['password_hash']);
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

        Base_view::$TITLE = Translate::td('Email confirmed successfully');
        Base_view::$MENU_POINT = Menu_point::Register;
        echo Base_view::header();
        echo Base_view::text(Translate::td('Email confirmed successfully'));
        echo Base_view::footer();
    }

    static private function handleSettingsForm()
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

    static private function handleSettingsRequest()
    {
        if (!Application::$authorizedInvestor) {
            Utility::location(self::BASE_URL);
        }
        Application::$authorizedInvestor->setFirstnameLastName(@$_POST['firstname'], @$_POST['lastname']);
        if (self::verifyPassword(@$_POST['password'])) {
            Application::$authorizedInvestor->changePassword(@$_POST['password']);
        }
        Utility::location(self::SETTINGS_URL);
    }

    static public function handleInviteFriendsForm($message = '')
    {
        if (!Application::$authorizedInvestor) {
            Utility::location(self::BASE_URL);
        }

        Base_view::$TITLE = 'Invite friends';
        Base_view::$MENU_POINT = Menu_point::Dashboard;
        echo Base_view::header();
        echo Investor_view::invite_friends($message);
        echo Base_view::footer();
    }

    static public function handleInviteFriendsRequest()
    {
        if (!Application::$authorizedInvestor) {
            Utility::location(self::BASE_URL);
        }
        $email = @$_POST['email'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Utility::location(self::INVITE_FRIENDS_URL . '?err=1&err_text=not a valid email');
        }
        $authorizedInvestorEmail = Application::$authorizedInvestor->email;
        $url = APPLICATION_URL . '/' . self::REGISTER_URL . '?referrer_code=' . Application::$authorizedInvestor->referrer_code;
        $html = <<<EOT
<h3>Invite</h3>
<h5>$authorizedInvestorEmail has invited you to join the group.</h5>
<p>Hello</p>
<p>$authorizedInvestorEmail has invited you to join the group Equinox and participate in Cryptaur pre-sale/token sale.</p>
<p>Please follow the <a href="$url">link</a> to accept the invitation:</p>
<p><a href="$url">$url</a></p>
EOT;
        if (Email::send($email, [], Translate::td('Invite friend'), $html, true)) {
            self::handleInviteFriendsForm("$email successfully invited");
        } else {
            Utility::location(self::INVITE_FRIENDS_URL . '?err=2&err_text=can not send email with invite');
        }
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

    static public function urlForRegistration($email, $firstname, $lastname, $eth_address, $referrer_id, $password)
    {
        $data = [
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'eth_address' => $eth_address,
            'referrer_id' => $referrer_id,
            'password_hash' => self::hashPassword($password)
        ];
        return APPLICATION_URL . '/' . self::REGISTER_CONFIRMATION_URL . '?d=' . Utility::encodeData($data);
    }
}