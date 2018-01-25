<?php

namespace core\controllers;

use core\engine\Application;
use core\engine\Email;
use core\engine\Utility;
use core\engine\DB;
use core\engine\Router;
use core\models\EtherWallet;
use core\models\EthQueue;
use core\models\Investor;
use core\secondfactor\API2FA;
use core\secondfactor\variants_2FA;
use core\translate\Translate;
use core\views\Base_view;
use core\views\Investor_view;
use core\views\Menu_point;

class Investor_controller
{
    static public $initialized = false;

    const BASE_URL = 'investor';
    const LOGIN_URL = 'investor/login';
    const SECONDFACTOR_URL = 'investor/two-factor';
    const SECONDFACTORDUAL_URL = 'investor/two-factor-dual';
    const SECONDFACTORSET_URL = 'investor/two-factor-set';
    const PHONEVERIFICATION_URL = 'investor/verify_phone';
    const SET_EMPTY_ETH_ADDRESS = 'investor/set_eth_address';
    const LOGOUT_URL = 'investor/logout';
    const RECOVER_URL = 'investor/recover';
    const CRYPTAURETHERWALLET_URL = 'investor/cryptauretherwallet';
    const CHANGE_PASSWORD_URL = 'investor/changepassword';
    const REGISTER_URL = 'investor/register';
    const PREVIOUS_SYSTEM_REGISTER_URL = 'syndicates/join';
    const REGISTER_CONFIRMATION_URL = 'investor/register_confirm';
    const SETTINGS_URL = 'investor/settings';
    const INVITE_FRIENDS_URL = 'investor/invite_friends';

    const SESSION_KEY = 'authorized_investor_id';
    const SESSION_KEY_TMP = 'authorized_investor_id_tmp';

    const PHONE_VERIFY_NUMBER = 'phone_verify_number';
    const CHOSEN_2FA_METHOD = 'chosen_2fa_method';
    const LAST_2FA_TRY = 'last_2fa_time';

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
            self::handleEmptyEthSetForm();
        }, self::SET_EMPTY_ETH_ADDRESS, Router::GET_METHOD);
        Router::register(function () {
            Base_view::$TITLE = 'Set eth address';
            echo Base_view::header();
            echo Investor_view::ethSetupForm2();
            echo Base_view::footer();
        }, self::SET_EMPTY_ETH_ADDRESS . '/test', Router::GET_METHOD);

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
            self::handleSecondfactorDualForm();
        }, self::SECONDFACTORDUAL_URL, Router::GET_METHOD);
        Router::register(function () {
            self::handleSecondfactorDualRequest();
        }, self::SECONDFACTORDUAL_URL, Router::POST_METHOD);

        Router::register(function () {
            self::handleSecondfactorSetForm();
        }, self::SECONDFACTORSET_URL, Router::GET_METHOD);
        Router::register(function () {
            self::handleSecondfactorSetRequest();
        }, self::SECONDFACTORSET_URL, Router::POST_METHOD);

        Router::register(function () {
            self::handlePhoneVerificationForm();
        }, self::PHONEVERIFICATION_URL, Router::GET_METHOD);
        Router::register(function () {
            self::handlePhoneVerificationRequest();
        }, self::PHONEVERIFICATION_URL, Router::POST_METHOD);

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
            self::handleCryptaurEtherWalletForm();
        }, self::CRYPTAURETHERWALLET_URL, Router::GET_METHOD);

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

    static public function isPassAllowed()
    {
        if (!Application::$authorizedInvestor) {
            Utility::location(self::LOGIN_URL);
        }
        if (!Application::$authorizedInvestor->eth_address) {
            Utility::location(Investor_controller::SET_EMPTY_ETH_ADDRESS);
        }
        Investor_controller::is2FACorrect();
    }

    static public function is2FACorrect()
    {
        if (USE_2FA) {
            if (!Application::$authorizedInvestor->preferred_2fa) {
                Utility::location(self::SECONDFACTORSET_URL);
            }
            if (!in_array(Application::$authorizedInvestor->preferred_2fa, API2FA::$allowedMethods)) {
                Utility::location(self::SECONDFACTORSET_URL . '?wrong_method=1');
            }
        }
    }

    static private function detectLoggedInInvestor()
    {
        $authorizedInvestor = Investor::getById(@$_SESSION[self::SESSION_KEY]);
        if ($authorizedInvestor) {
            Application::$authorizedInvestor = $authorizedInvestor;
        }
    }

    static public function loginWithId($investorId)
    {
        session_start();
        $_SESSION[self::SESSION_KEY] = $investorId;
        session_write_close();
    }

    static private function handleEmptyEthSetForm()
    {
        if (!Application::$authorizedInvestor) {
            Utility::location(self::LOGIN_URL);
        }
        if (Application::$authorizedInvestor->eth_address) {
            Utility::location(self::BASE_URL);
        }
        Investor_controller::is2FACorrect();

        $wallet = EthQueue::getWallet(Application::$authorizedInvestor->id);
        if (!is_null($wallet)) {
            $eth_address = $wallet->eth_address;
            $investor = Application::$authorizedInvestor;
            $investor->setEthAddress($eth_address);

            if (count(DB::get("
                SELECT `id`
                FROM `investors_waiting_tokens`
                WHERE `investor_id` = ?
                LIMIT 1;
            ", [$investor->id])) > 0) {
                DB::set("DELETE FROM `investors_waiting_tokens` WHERE `investor_id` = ?", [$investor->id]);
                if ($investor->tokens_count > 0) {
                    $data = [
                        'tokens' => $investor->tokens_count
                    ];
                    EthQueue::mintTokens(EthQueue::TYPE_MINT_OLD_INVESTOR_INIT, $investor->id, $data, $investor->eth_address, $investor->tokens_count);
                }
            }
        }

        Base_view::$TITLE = 'Set eth address';
        echo Base_view::header();
        echo Investor_view::ethSetupForm();
        echo Base_view::footer();
    }

    static private function handleSecondfactorSetForm($message = '')
    {
        if (!Application::$authorizedInvestor) {
            Utility::location(self::BASE_URL);
        }
        Base_view::$TITLE = 'Two-factor authentication settings';
        Base_view::$MENU_POINT = Menu_point::Settings;
        echo Base_view::header();
        echo Investor_view::secondfactorSetForm($message);
        echo Base_view::footer();
    }

    static private function handleSecondfactorSetRequest()
    {
        if (!Application::$authorizedInvestor) {
            Utility::location(self::BASE_URL);
        }
        if (!in_array(@$_POST['2fa_method'], API2FA::$allowedMethods)) {
            Utility::location(self::SECONDFACTORSET_URL);
        }
        $urlErrors = [];
        if (USE_2FA) {
            if (
                $_POST['2fa_method'] == variants_2FA::sms
                || $_POST['2fa_method'] == variants_2FA::both

            ) {
                if (@$_POST['phone'] == "") {
                    $urlErrors[] = 'phone_req_err=1';
                } else {
                    $phone = Utility::clear_except_numbers($_POST['phone']);
                    if (
                        Application::$authorizedInvestor->phone != ""
                        && Application::$authorizedInvestor->phone == $phone
                    ) {
                        Application::$authorizedInvestor->set2faMethod($_POST['2fa_method']);
                        $urlErrors[] = 'success=1';
                    } else {
                        session_start();
                        $_SESSION[self::PHONE_VERIFY_NUMBER] = $phone;
                        $_SESSION[self::CHOSEN_2FA_METHOD] = $_POST['2fa_method'];
                        session_write_close();
                        $sent = API2FA::send_sms($phone);
                        if ($sent == FALSE) {
                            Utility::location(self::SECONDFACTORSET_URL . '?send_sms_err=1');
                        }
                        Utility::location(self::PHONEVERIFICATION_URL);
                    }
                }
            } else {
                Application::$authorizedInvestor->set2faMethod($_POST['2fa_method']);
                $urlErrors[] = 'success=1';
            }
        }
        Utility::location(self::SECONDFACTORSET_URL . '?' . implode('&', $urlErrors));
    }

    static private function handlePhoneVerificationForm($message = '')
    {
        if (!Application::$authorizedInvestor) {
            Utility::location(self::BASE_URL);
        }
        Base_view::$TITLE = 'Phone number verification';
        Base_view::$MENU_POINT = Menu_point::Settings;
        echo Base_view::header();
        echo Investor_view::phoneVerificationForm($message);
        echo Base_view::footer();
    }

    static private function handlePhoneVerificationRequest()
    {
        if (
            !Application::$authorizedInvestor
            || !isset($_SESSION[self::PHONE_VERIFY_NUMBER])
            || !isset($_SESSION[self::CHOSEN_2FA_METHOD])
            || !isset($_POST['otp'])
        ) {
            Utility::location(self::BASE_URL);
        }
        $urlErrors = [];
        $checked = API2FA::check($_POST['otp']);
        if ($checked === TRUE) {
            Application::$authorizedInvestor->setPhone($_SESSION[self::PHONE_VERIFY_NUMBER]);
            Application::$authorizedInvestor->set2faMethod($_SESSION[self::CHOSEN_2FA_METHOD]);
            session_start();
            unset($_SESSION[self::PHONE_VERIFY_NUMBER]);
            unset($_SESSION[self::CHOSEN_2FA_METHOD]);
            session_write_close();
            $urlErrors[] = 'success=1';
            $urlErrors[] = 'phone_verified=1';
        } else {
            $sent = API2FA::send_sms($_SESSION[self::PHONE_VERIFY_NUMBER]);
            if ($sent == FALSE) {
                $urlErrors[] = 'send_sms_err=1';
            } else {
                Utility::location(self::PHONEVERIFICATION_URL . '?wrong_code=1');
            }
        }
        Utility::location(self::SECONDFACTORSET_URL . '?' . implode('&', $urlErrors));
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

    static private function sent2FARequest($investorId)
    {
        if (USE_2FA == FALSE) {
            return FALSE;
        }
        $user = Investor::getById($investorId);
        if (!in_array($user->preferred_2fa, API2FA::$allowedMethods)) {
            return FALSE;
        } elseif ($user->preferred_2fa == variants_2FA::email) {
            return API2FA::send_email($user->email);
        } elseif ($user->preferred_2fa == variants_2FA::sms) {
            return API2FA::send_sms($user->phone);
        } elseif ($user->preferred_2fa == variants_2FA::both) {
            return API2FA::send_both($user->email, $user->phone);
        }
        return FALSE;
    }
    
    static private function investor2FAFormType($investorId)
    {
        if (USE_2FA == FALSE) {
            return NULL;
        }
        $user = Investor::getById($investorId);
        //0 - single-code form, 1 - dual-code form
        if ($user->preferred_2fa == variants_2FA::none) {
            return NULL;
        } elseif ($user->preferred_2fa == variants_2FA::both) {
            return 1;
        }
        return 0;
    }

    static private function handleLoginRequest()
    {
        $email = trim(@$_POST['email']);
        $password = trim(@$_POST['password']);
        $investorId = @Investor::getInvestorIdByEmailPassword($email, $password);
        $investor = null;
        if ($investorId) {
            $sfa_used = self::investor2FAFormType($investorId); //TRUE if user USE the 2FA
            if (USE_2FA == FALSE || is_null($sfa_used)) {
                self::loginWithId($investorId);
                $investor = Investor::getById($investorId);
            } else {
                session_start();
                $_SESSION[self::SESSION_KEY_TMP] = $investorId;
                session_write_close();
                if ($sfa_used == 0) {
                    Utility::location(self::SECONDFACTOR_URL);
                } else {
                    Utility::location(self::SECONDFACTORDUAL_URL);
                }
            }
        } else {
            $investorId = Investor::investorId_previousSystemCredentials($email, $password);
            if ($investorId > 0) {
                self::loginWithId($investorId);
                $investor = Investor::getById($investorId);
                $investor->changePassword($password);
            }
        }
        if ($investor) {
            Utility::location(self::BASE_URL);
        }
        Utility::location(self::LOGIN_URL . '?err=3671&err_text=wrong credentials');
    }

    static private function handleSecondfactorForm($message = '')
    {
        if (
            Application::$authorizedInvestor
            || !isset($_SESSION[self::SESSION_KEY_TMP])
        ) {
            Utility::location(self::BASE_URL);
        }
        if (isset($_GET['sent'])) {
            $time = time();
            if (
                isset($_SESSION[self::LAST_2FA_TRY])
                && $time - $_SESSION[self::LAST_2FA_TRY] < 180
            ) {
                $message = Translate::td('You cannot sent another code until 3 minutes will expire');
            } else {
                session_start();
                $_SESSION[self::LAST_2FA_TRY] = $time;
                session_write_close();
                if (self::sent2FARequest($_SESSION[self::SESSION_KEY_TMP])) {
                    $message = Translate::td('Authentication code has been sended using preferred method');
                } else { //potential case when some methods have been disabled or broken
                    session_start();
                    unset($_SESSION[self::SESSION_KEY_TMP]);
                    unset($_SESSION[self::LAST_2FA_TRY]);
                    session_write_close();
                    self::loginWithId($_SESSION[self::SESSION_KEY_TMP]);
                    Utility::location(self::BASE_URL);
                }
            }
        }
        Base_view::$TITLE = 'Two-Factor Authentication';
        Base_view::$MENU_POINT = Menu_point::Login;
        echo Base_view::header();
        echo Investor_view::secondfactorForm($message);
        echo Base_view::footer();
    }

    static private function handleSecondfactorRequest()
    {
        if (
            !isset($_SESSION[self::SESSION_KEY_TMP]) ||
            !isset($_POST['otp'])
        ) {
            Utility::location(self::BASE_URL);
        }

        $investorId = $_SESSION[self::SESSION_KEY_TMP];

        session_start();
        unset($_SESSION[self::SESSION_KEY_TMP]);
        unset($_SESSION[self::LAST_2FA_TRY]);
        session_write_close();

        $checked = API2FA::check($_POST['otp']);
        if ($checked === TRUE) {
            self::loginWithId($investorId);
            Utility::location(self::BASE_URL);
        }
        Utility::location(self::LOGIN_URL . '?err=6538&err_text=wrong authentication code');
    }

    static private function handleSecondfactorDualForm($message = '')
    {
        if (
            Application::$authorizedInvestor
            || !isset($_SESSION[self::SESSION_KEY_TMP])
        ) {
            Utility::location(self::BASE_URL);
        }
        if (isset($_GET['sent'])) {
            $time = time();
            if (
                isset($_SESSION[self::LAST_2FA_TRY])
                && $time - $_SESSION[self::LAST_2FA_TRY] < 180
            ) {
                $message = Translate::td('You cannot sent another codes until 3 minutes will expire');
            } else {
                session_start();
                $_SESSION[self::LAST_2FA_TRY] = $time;
                session_write_close();
                if (self::sent2FARequest($_SESSION[self::SESSION_KEY_TMP])) {
                    $message = Translate::td('Authentication codes have been sended');
                } else { //potential case when some methods have been disabled or broken
                    session_start();
                    unset($_SESSION[self::SESSION_KEY_TMP]);
                    unset($_SESSION[self::LAST_2FA_TRY]);
                    session_write_close();
                    self::loginWithId($_SESSION[self::SESSION_KEY_TMP]);
                    Utility::location(self::BASE_URL);
                }
            }
        }
        Base_view::$TITLE = 'Two-Factor Authentication';
        Base_view::$MENU_POINT = Menu_point::Login;
        echo Base_view::header();
        echo Investor_view::secondfactorDualForm($message);
        echo Base_view::footer();
    }

    static private function handleSecondfactorDualRequest()
    {
        if (
            !isset($_SESSION[self::SESSION_KEY_TMP]) ||
            !isset($_POST['code_1']) ||
            !isset($_POST['code_2'])
        ) {
            Utility::location(self::BASE_URL);
        }

        $investorId = $_SESSION[self::SESSION_KEY_TMP];

        session_start();
        unset($_SESSION[self::SESSION_KEY_TMP]);
        unset($_SESSION[self::LAST_2FA_TRY]);
        session_write_close();

        $checked = API2FA::check_both($_POST['code_1'], $_POST['code_2']);
        if ($checked === TRUE) {
            self::loginWithId($investorId);
            Utility::location(self::BASE_URL);
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
        if (@$_GET['test']) {
            echo Investor_view::registerForm2($data, $error);
        } else {
            echo Investor_view::registerForm($data, $error);
        }
        echo Base_view::footer();
    }

    /**
     * @param string $password
     * @return bool
     */
    static private function verifyPassword($password)
    {
        return !!preg_match('/^[0-9A-Za-z!"#$%&\'()*+,-.\/:;<=>?@\[\]^_`{|}~]{6,50}$/', $password);
    }

    static private function handleRegistrationRequest()
    {
        $email = trim(@$_POST['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::handleRegistrationForm($_POST, 'not a valid email');
            return;
        }
        if (Investor::isExistWithParams($email)) {
            self::handleRegistrationForm($_POST, 'email already in use');
            return;
        }
        $referrerId = 0;
        $referrer_code = trim(@$_POST['referrer_code']);
        if ($referrer_code) {
            $referrerId = Investor::getReferrerIdByCode($referrer_code);
            if (!$referrerId) {
                self::handleRegistrationForm($_POST, 'not a valid referrer code');
                return;
            }
        }
        $password = trim(@$_POST['password']);
        if (!self::verifyPassword($password)) {
            self::handleRegistrationForm($_POST, 'not a valid password, use more than 6 characters');
            return;
        }

        $firstname = trim(@$_POST['firstname']);
        $lastname = trim(@$_POST['lastname']);
        $confirmationUrl = self::urlForRegistration($email, $firstname, $lastname, $referrerId, $password);
        Email::send($email, [], 'Cryptaur: email confirmation', "<p><a href=\"$confirmationUrl\">Confirm email to finish registration</a></p>", true);

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
            Email::send($investor->email, [], 'Password recovery', $html, true);
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
        $registerResult = Investor::registerUser($data['email'], $data['firstname'], $data['lastname'], $data['referrer_id'], $data['password_hash']);
        if ($registerResult < 0) {
            Base_view::$TITLE = 'Email confirmation problem';
            Base_view::$MENU_POINT = Menu_point::Register;
            echo Base_view::header();
            echo Base_view::text("Something went wrong with user registration ($registerResult)");
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
        Investor_controller::isPassAllowed();

        Base_view::$TITLE = 'Settings';
        Base_view::$MENU_POINT = Menu_point::Settings;
        echo Base_view::header();
        if ($_GET['test']) {
            echo Investor_view::settings2();
        } else {
            echo Investor_view::settings();
        }
        echo Base_view::footer();
    }

    static private function handleCryptaurEtherWalletForm()
    {
        Investor_controller::isPassAllowed();

        EtherWallet::getByInvestorId(Application::$authorizedInvestor->id);

        Base_view::$TITLE = 'Cryptaur Ether Wallet';
        Base_view::$MENU_POINT = Menu_point::Cryptaur_ether_wallet;
        echo Base_view::header();
        echo Investor_view::cryptauretherwallet();
        echo Base_view::footer();
    }

    static private function handleSettingsRequest()
    {
        if (!Application::$authorizedInvestor) {
            Utility::location(self::BASE_URL);
        }
        Application::$authorizedInvestor->setFirstnameLastName(@$_POST['firstname'], @$_POST['lastname']);
        $urlErrors = [];
        if (self::verifyPassword(@$_POST['password'])) {
            Application::$authorizedInvestor->changePassword(@$_POST['password']);
        } else if (@strlen($_POST['password']) > 0) {
            $urlErrors[] = 'password_err=1';
        }
//        if (Utility::validateEthAddress(@$_POST['eth_address'])) {
//            Application::$authorizedInvestor->setEthAddress(@$_POST['eth_address']);
//        } else if (@strlen($_POST['eth_address']) > 0) {
//            $urlErrors[] = 'eth_address_err=1';
//        }
        Utility::location(self::SETTINGS_URL . '?' . implode('&', $urlErrors));
    }

    static public function handleInviteFriendsForm($message = '')
    {
        Investor_controller::isPassAllowed();

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
        if (Email::send($email, [], 'Invite friend', $html, true)) {
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

    static public function urlForRegistration($email, $firstname, $lastname, $referrer_id, $password)
    {
        $data = [
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'referrer_id' => $referrer_id,
            'password_hash' => self::hashPassword($password)
        ];
        return APPLICATION_URL . '/' . self::REGISTER_CONFIRMATION_URL . '?d=' . Utility::encodeData($data);
    }
}