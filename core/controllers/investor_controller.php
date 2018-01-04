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
    const SECONDFACTOR_URL = 'investor/secondfactor';
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
    
    const PREVIOUS_2FA_LOGIN_FLAG = 'previous_system_2fa_login_flag';
    const PREVIOUS_2FA_PASSWORD_TMP = 'previous_system_2fa_password_tmp';
    const SESSION_KEY_TMP = 'authorized_investor_id_tmp';
    const SFA_OTP_ID = 'secondfactor_otp_id';
    const SFA_UNIQUE_ID = 'secondfactor_unique_id';

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
            self::handleLoginForm();
        }, self::LOGIN_URL, Router::GET_METHOD);
        Router::register(function () {
            self::handleLoginRequest();
        }, self::LOGIN_URL, Router::POST_METHOD);

        Router::register(function () {
            self::handleSecondfactorForm();
        }, self::SECONDFACTOR_URL, Router::GET_METHOD);
        Router::register(function () {
            self::handleSecondfactorRequest();
        }, self::SECONDFACTOR_URL, Router::POST_METHOD);

        Router::register(function () {
            if (!Application::$authorizedInvestor) {
                Utility::location(self::BASE_URL);
            }
            self::handleLogoutRequest();
        }, self::LOGOUT_URL);

        Router::register(function () {
            self::handleRegistrationForm();
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

    static public function loginWithId($investorId)
    {
        session_start();
        $_SESSION[self::SESSION_KEY] = $investorId;
        session_write_close();
    }
    
    static private function previousPreLogin($investorId, $password)
    {
        session_start();
        $_SESSION[self::PREVIOUS_SYSTEM_ID] = $investorId;
        $_SESSION[self::PREVIOUS_SYSTEM_PASSWORD] = $password;
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
        $isOk = false;
        if ($investor->tokens_count == 0) {
            $isOk = true;
        } else {
            $mintResult = Bounty_controller::mintTokens($investor, $investor->tokens_count);
            if (is_string($mintResult)) {
                $txid = $mintResult;
                Utility::log('mint3/' . Utility::microtime_float(), [
                    'investor' => $investorId,
                    'txid' => $txid,
                    'time' => time()
                ]);
                $isOk = true;
            }
        }

        if ($isOk) {
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

    static private function handleLoginForm($message = '')
    {
        if (Application::$authorizedInvestor) {
            Utility::location(self::BASE_URL);
        }
        Base_view::$TITLE = 'Login';
        Base_view::$MENU_POINT = Menu_point::Login;
        echo Base_view::header();
        echo Investor_view::loginForm($message);
        echo Base_view::footer();
    }
    
    static private function sent2FaOtpRequest($investorId)
    {
        if(USE_2FA == FALSE)
        {
            return FALSE;
        }
        //TODO - make some rework when 2fa by user's wish will be implemented
        $user = Investor::getById($investorId);
        $email = $user->email;
        $ga_id = "ga_id_$investorId";
        $ga_user = \core\gauthify\GAuthify::get_user($ga_id); //get user from GAuthify
        if(is_null($ga_user)) { //else create it
            $ga_user = \core\gauthify\GAuthify::create_user($ga_id, md5($ga_user), $email);
        }
        $result = \core\gauthify\GAuthify::send_email($ga_id);
        if(!is_null($result)) {
            session_start();
            $_SESSION[self::SFA_UNIQUE_ID] = $ga_id;
            $_SESSION[self::SFA_OTP_ID] = $result['otp_id'];
            session_write_close();
            return TRUE;
        }
        return FALSE;
    }

    static private function handleLoginRequest()
    {
        $investorId = @Investor::getInvestorIdByEmailPassword($_POST['email'], $_POST['password']);
        if ($investorId) {
            $sfa_used = self::sent2FaOtpRequest($investorId); //TRUE if user USE the 2FA
            if(USE_2FA == FALSE || $sfa_used == FALSE) {
                self::loginWithId($investorId);
                Utility::location(self::BASE_URL);
            }
            elseif($sfa_used) {
                session_start();
                $_SESSION[self::SESSION_KEY_TMP] = $investorId;
                $_SESSION[self::PREVIOUS_2FA_LOGIN_FLAG] = FALSE;
                session_write_close();
                Utility::location(self::SECONDFACTOR_URL);
            }
        } else {
            $investorId = Investor::investorId_previousSystemCredentials($_POST['email'], $_POST['password']);
            if ($investorId > 0) {
                $sfa_used = self::sent2FaOtpRequest($investorId); //TRUE if user USE the 2FA
                if(USE_2FA == FALSE || $sfa_used == FALSE) {
                    self::previousPreLogin($investorId, $_POST['password']);
                    Utility::location(self::SET_ETH_ADDRESS);
                }
                elseif($sfa_used) {
                    session_start();
                    $_SESSION[self::PREVIOUS_2FA_PASSWORD_TMP] = $_POST['password'];
                    $_SESSION[self::SESSION_KEY_TMP] = $investorId;
                    $_SESSION[self::PREVIOUS_2FA_LOGIN_FLAG] = TRUE;
                    session_write_close();
                    Utility::location(self::SECONDFACTOR_URL);
                }
            }
        }
        Utility::location(self::LOGIN_URL . '?err=3671&err_text=wrong credentials');
    }

    static private function handleSecondfactorForm($message = '')
    {
        if (Application::$authorizedInvestor) {
            Utility::location(self::BASE_URL);
        }
        Base_view::$TITLE = 'Second Factor Authentication';
        Base_view::$MENU_POINT = Menu_point::Login;
        echo Base_view::header();
        echo Investor_view::secondfactorForm($message);
        echo Base_view::footer();
    }

    static private function handleSecondfactorRequest()
    {
        if (
            !isset($_SESSION[self::PREVIOUS_2FA_LOGIN_FLAG]) ||
            !isset($_SESSION[self::SESSION_KEY_TMP]) ||
            !isset($_SESSION[self::SFA_OTP_ID]) ||
            !isset($_SESSION[self::SFA_UNIQUE_ID]) ||
            !isset($_POST['otp'])
        ) {
            Utility::location(self::BASE_URL);
        }
        
        $unique_id = $_SESSION[self::SFA_UNIQUE_ID];
        $otp_id = $_SESSION[self::SFA_OTP_ID];
        $investorId = $_SESSION[self::SESSION_KEY_TMP];
        $p_login = $_SESSION[self::PREVIOUS_2FA_LOGIN_FLAG];
        $password_tmp = isset($_SESSION[self::PREVIOUS_2FA_PASSWORD_TMP]) ? $_SESSION[self::PREVIOUS_2FA_PASSWORD_TMP] : NULL;
        
        session_start();
        unset($_SESSION[self::SFA_OTP_ID]);
        unset($_SESSION[self::SFA_UNIQUE_ID]);
        unset($_SESSION[self::SESSION_KEY_TMP]);
        unset($_SESSION[self::PREVIOUS_2FA_LOGIN_FLAG]);
        if(isset($_SESSION[self::PREVIOUS_2FA_PASSWORD_TMP]))
        {
            unset($_SESSION[self::PREVIOUS_2FA_PASSWORD_TMP]);
        }
        session_write_close();
        
        $otp = $_POST['otp'];
        $checked = \core\gauthify\GAuthify::check($unique_id, $otp, $otp_id);
        if($checked == 1) {
            if($p_login === FALSE) {
                self::loginWithId($investorId);
                Utility::location(self::BASE_URL);
            }
            elseif(
                $p_login === TRUE && 
                !is_null($password_tmp)
            ) {
                self::previousPreLogin($investorId, $password_tmp);
                Utility::location(self::SET_ETH_ADDRESS);
            }
        }
        Utility::location(self::LOGIN_URL . '?err=6538&err_text=wrong authentication code');
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
     * @param [] $data
     * @param string $error
     */
    static private function handleRegistrationForm($data = [], $error = '')
    {
        if (Application::$authorizedInvestor) {
            Utility::location(self::BASE_URL);
        }
        Base_view::$TITLE = 'Registration';
        Base_view::$MENU_POINT = Menu_point::Register;
        echo Base_view::header();
        echo Investor_view::registerForm($data, $error);
        echo Base_view::footer();
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
            self::handleRegistrationForm($_POST, 'not a valid email');
            return;
        }
        if (!Utility::validateEthAddress(@$_POST['eth_address'])) {
            self::handleRegistrationForm($_POST, 'not a valid eth address');
            return;
        }
        if (Investor::isExistWithParams($_POST['email'])) {
            self::handleRegistrationForm($_POST, 'email already in use');
            return;
        }
        $referrerId = 0;
        if (@$_POST['referrer_code']) {
            $referrerId = Investor::getReferrerIdByCode(@$_POST['referrer_code']);
            if (!$referrerId) {
                self::handleRegistrationForm($_POST, 'not a valid referrer code');
                return;
            }
        }
        if (!self::verifyPassword(@$_POST['password'])) {
            self::handleRegistrationForm($_POST, 'not a valid password, use more than 6 characters');
            return;
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
                <h3 style="font-size: 32px;font-weight: 300;margin: 30px 0px;color: rgba(146, 146, 146, 1);line-height: 2.5em;font-family: sans-serif;font-style: normal;text-align: center;text-transform: none;">Forgot password</h3>
                <p style="font-size: 14px;font-style: normal;font-weight: 400;line-height: 1.8;margin: 0;text-align: justify;padding: 10px 20px 0px;">Please follow the <a href="$url">link</a> to change password to <strong>$password</strong>:</p>
                <p style="font-size: 14px;font-style: normal;font-weight: 400;line-height: 1.8;margin: 0;text-align: justify;padding: 10px 20px 0px;"><a href="$url">$url</a></p>
                <p style="font-size: 14px;font-style: normal;font-weight: 400;line-height: 1.8;margin: 0;text-align: justify;padding: 10px 20px 0px;">Link will be working for 48 hours.</p>
EOT;
            Email::send($investor->email, [], Translate::td('Password recovery'), $html, true);
        }
        self::handleLoginForm(Translate::td('If the user exists then he was sent a new password'));
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
        self::handleLoginForm(Translate::td('Password successfully changed'));
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
<h3 style="font-size: 32px;font-weight: 300;margin: 30px 0px;color: rgba(146, 146, 146, 1);line-height: 2.5em;font-family: sans-serif;font-style: normal;text-align: center;text-transform: none;">Invite</h3>
<h5 style="font-size: 16px;font-weight: 300;color: rgba(146, 146, 146, 1);font-family: sans-serif;font-style: normal;text-align: center;">$authorizedInvestorEmail has invited you to join the group.</h5>
<p style="font-size: 14px;font-style: normal;font-weight: 400;line-height: 1.8;margin: 0;text-align: justify;padding: 10px 20px 0px;">Hello</p>
<p style="font-size: 14px;font-style: normal;font-weight: 400;line-height: 1.8;margin: 0;text-align: justify;padding: 10px 20px 0px;">$authorizedInvestorEmail has invited you to join the group Equinox and participate in Cryptaur pre-sale/token sale.</p>
<p style="font-size: 14px;font-style: normal;font-weight: 400;line-height: 1.8;margin: 0;text-align: justify;padding: 10px 20px 0px;">Please follow the <a href="$url">link</a> to accept the invitation:</p>
<p style="font-size: 14px;font-style: normal;font-weight: 400;line-height: 1.8;margin: 0;text-align: justify;padding: 10px 20px 0px;"><a href="$url">$url</a></p>
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