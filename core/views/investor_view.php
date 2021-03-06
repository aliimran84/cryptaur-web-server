<?php

namespace core\views;

use core\controllers\Investor_controller;
use core\controllers\EtherWallet_controller;
use core\engine\Application;
use core\engine\Utility;
use core\models\Coin;
use core\models\EtherWallet;
use core\models\EthQueue;
use core\secondfactor\API2FA;
use core\secondfactor\variants_2FA;
use core\translate\Translate;

class Investor_view
{
    static public function loginForm($image = NULL, $message = '')
    {
        ob_start();
        ?>
        <div class="row">
            <div class="col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <h3><?= Translate::td('Cryptaur login') ?></h3>
                <div class="row">
                    <form class="login col s12" action="<?= Investor_controller::LOGIN_URL ?>" method="post" autocomplete="off">
                        <?php if (isset($_GET['err'])) { ?>
                            <label class="red-text"><?= Translate::td('Error') ?> <?= $_GET['err'] ?>
                                : <?= Translate::td($_GET['err_text']) ?></label>
                        <?php } ?>
                        <?php if ($message) { ?>
                            <label class="blue-text"><?= $message ?></label>
                        <?php } ?>
                        <input type="email" name="email" placeholder="Email">
                        <input type="password" name="password" placeholder="<?= Translate::td('Password') ?>" autocomplete="new-password">
                        <?php if (!is_null($image)) { ?>
                            <div class="captcha">
                                <image src="data:image/png;base64,<?= $image ?>">
                                    <div class="captcha-input">
                                        <p><?= Translate::td('Enter security code') ?></p>
                                        <input type="text" name="captcha" placeholder="Captcha" required>
                                    </div>
                            </div>
                        <?php } ?>
                        <div class="row center">
                            <button type="submit" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                <?= Translate::td('Login') ?>
                            </button>
                            <!--<p>Forgot your account password? <a href="#">Recover</a></p>-->
                        </div>
                        <h5><?= Translate::td('Forgot your account login') ?>?</h5>
                        <div class="row center">
                            <a href="<?= Investor_controller::RECOVER_URL ?>" class="waves-effect waves-light btn btn-login"><?= Translate::td('Recover') ?></a>
                        </div>
                        <h5><?= Translate::td('Not a member yet') ?>?</h5>
                        <div class="row center">
                            <a href="<?= Investor_controller::REGISTER_URL ?>" class="waves-effect waves-light btn btn-login"><?= Translate::td('Register') ?></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    static public function secondfactorSetForm($message = '')
    {
        $list2FA = API2FA::$allowedMethods;
        $methodSetted = in_array(Application::$authorizedInvestor->preferred_2fa, $list2FA);
        $country_codes = json_decode(file_get_contents(PATH_TO_WEB_ROOT_DIR . '/scripts/phone_codes.json'), TRUE);
        ob_start();
        ?>
        <?= self::tabsSettings('secondFactor') ?>
        <div class="row">
            <div class="settings-block col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <form action="<?= Investor_controller::SECONDFACTORSET_URL ?>" method="post" autocomplete="off">
                    <h3><?= Translate::td('Two-factor authentication settings') ?></h3>
                    <h5 class="blue-text"><?= Translate::td('You must choose two-factor authentication option') ?></h5>
                    <?php if (isset($_GET['phone_req_err'])) { ?>
                        <label class="red-text"><?= Translate::td('You cannot select SMS-based second factor authentication methods without verified phone number') ?></label>
                    <?php } ?>
                    <?php if (isset($_GET['wrong_phone_err'])) { ?>
                        <label class="red-text"><?= Translate::td('Wrong format of the phone number') ?></label>
                    <?php } ?>
                    <?php if (isset($_GET['send_sms_err'])) { ?>
                        <label class="red-text"><?= Translate::td('Unable to send SMS, service temporary disabled') ?></label>
                    <?php } ?>
                    <?php if (isset($_GET['wrong_method'])) { ?>
                        <label class="red-text"><?= Translate::td('Previously selected method now disabled, you must choose another') ?></label>
                    <?php } ?>
                    <?php if (isset($_GET['success'])) { ?>
                        <label class="blue-text"><?= Translate::td('Two-factor authentication method has been set') ?></label>
                    <?php } ?>
                    <?php if (isset($_GET['phone_verified'])) { ?>
                        <label class="blue-text"><?= Translate::td('You successfully verified your phone number') ?></label>
                    <?php } ?>
                    <div class="row">
                        <?= Translate::td('Preferred two-factor authentication method') ?>:
                        <select id="2fa_method" name="2fa_method">
                            <?php foreach ($list2FA AS $var) { ?>
                                <option
                                    <?php if (
                                        Application::$authorizedInvestor->preferred_2fa == $var
                                        || (!$methodSetted && $var == variants_2FA::sms)
                                    ) { ?>
                                        selected=""
                                    <?php } ?>
                                        value="<?= $var ?>"
                                >
                                    <?= Translate::td($var) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div
                            id="phone_row"
                            class="row"
                        <?php if (
                            Application::$authorizedInvestor->preferred_2fa != variants_2FA::sms
                            && $methodSetted == TRUE
                        ) { ?>
                            style="display:none"
                        <?php } ?>
                    >
                        <span><?= Translate::td('Phone, mobile (only digits)') ?>:</span><br/>
                        <h5><?= Application::$authorizedInvestor->phone ?></h5>
                        <select class="phone_code" name="code" required="">
                            <option disabled="" selected="">-</option>
                            <?php foreach ($country_codes as $set) { ?>
                                <option><?= $set['code'] ?> <?= $set['name'] ?></option>
                            <?php } ?>
                        </select>
                        <input
                                type="text"
                                name="phone"
                                style="width: 50%; font-size: .75em;"
                                placeholder="<?= Translate::td('Phone, mobile (only digits)') ?>"
                                autocomplete="nope"
                                pattern="[0-9]{2,15}"
                        >
                        <span><?= Translate::td('e.g. 79997774433') ?></span><br/>
                        <span><?= Translate::td('Use only numbers') ?></span>
                    </div>
                    <button type="submit" class="waves-effect waves-light btn" style="width: 100%">
                        <?= Translate::td('Set') ?>
                    </button>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    static public function registerPhoneVerificationForm($message = '')
    {
        ob_start();
        ?>
        <div class="row">
            <div class="col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <h3><?= Translate::td('Phone number verification') ?></h3>
                <div class="row">
                    <form class="login col s12" action="<?= Investor_controller::REGISTER_PHONEVERIFICATION_URL ?>" method="post" autocomplete="off">
                        <h5>
                            <?= Translate::td('You must verify phone number') ?>.<br/>
                            <?= Translate::td("Click button to send the code") ?>
                        </h5>
                        <?php if (isset($_GET['err'])) { ?>
                            <label class="red-text"><?= Translate::td('Error') ?> <?= $_GET['err'] ?>
                                : <?= Translate::td($_GET['err_text']) ?></label>
                        <?php } ?>
                        <?php if ($message) { ?>
                            <label class="blue-text"><?= $message ?></label>
                        <?php } ?>
                        <?php if (isset($_GET['sent'])) { ?>
                            <input type="text" name="otp" placeholder="<?= Translate::td('Authentication code') ?>">
                            <div class="row center">
                                <button type="submit" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                    <?= Translate::td('Verify') ?>
                                </button>
                            </div>
                        <?php } ?>
                        <div class="row center">
                            <a href="<?= Investor_controller::REGISTER_PHONEVERIFICATION_URL ?>?sent=1" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                <?= isset($_GET['sent']) ? Translate::td('Re-send') : Translate::td('Send') ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    static public function phoneVerificationForm($message = '')
    {
        ob_start();
        ?>
        <div class="row">
            <div class="col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <h3><?= Translate::td('Phone number verification') ?></h3>
                <div class="row">
                    <form class="login col s12" action="<?= Investor_controller::PHONEVERIFICATION_URL ?>" method="post" autocomplete="off">
                        <?php if (isset($_GET['wrong_code'])) { ?>
                            <label class="red-text"><?= Translate::td('Wrong code, we have been sended another') ?></label>
                        <?php } ?>
                        <?php if ($message) { ?>
                            <label class="blue-text"><?= $message ?></label>
                        <?php } ?>
                        <h5><?= Translate::td('Input code, that you get with SMS') ?></h5>
                        <input type="text" name="otp" placeholder="<?= Translate::td('Authentication code') ?>">
                        <div class="row center">
                            <button type="submit" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                <?= Translate::td('Accept') ?>
                            </button>
                        </div>
                        <div class="row center">
                            <a href="<?= Investor_controller::SECONDFACTORSET_URL ?>" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                <?= Translate::td('Cancel') ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * @param string $image
     * @param [] $data
     * @param string $error
     * @return string HTML
     */
    static public function registerForm($image, $data = [], $error = '')
    {
        $referrer_code = '';
        if (isset($_GET['referrer_code'])) {
            $referrer_code = $_GET['referrer_code'];
        } else if (isset($data['referrer_code'])) {
            $referrer_code = $data['referrer_code'];
        }
        if ($referrer_code) {
            session_start();
            $_SESSION['registration_referrer_code'] = $referrer_code;
            session_write_close();
        } else if (isset($_SESSION['registration_referrer_code'])) {
            $referrer_code = $_SESSION['registration_referrer_code'];
        }
        $country_codes = json_decode(file_get_contents(PATH_TO_WEB_ROOT_DIR . '/scripts/phone_codes.json'), TRUE);
        ob_start();
        ?>
        <div class="row">
            <div class="col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <h3><?= Translate::td('Cryptaur registration') ?></h3>
                <div class="row">
                    <form class="registration col s12" action="<?= Investor_controller::REGISTER_URL ?>" method="post" autocomplete="off">
                        <?php if (isset($_GET['err'])) { ?>
                            <label class="red-text"><?= Translate::td('Error') ?> <?= $_GET['err'] ?>
                                : <?= Translate::td($_GET['err_text']) ?></label>
                        <?php } ?>
                        <?php if ($error) { ?>
                            <label class="red-text"><?= Translate::td('Error') ?>: <?= Translate::td($error) ?></label>
                        <?php } ?>
                        <input type="text" name="firstname" placeholder="<?= Translate::td('First name') ?>" value="<?= @$data['firstname'] ?>">
                        <input type="text" name="lastname" placeholder="<?= Translate::td('Last name') ?>" value="<?= @$data['lastname'] ?>">
                        <input type="email" name="email" placeholder="Email" value="<?= @$data['email'] ?>" autocomplete="nope">
                        <!--<select class="phone_code" name="code" required="">
                            <option 
                                disabled="" 
                                <?php if (!isset($data['code'])) { ?>
                                selected="" 
                                <?php } ?>
                            >-</option>
                            <?php foreach ($country_codes as $set) { ?>
                            <option 
                                value="<?= $set['code'] ?>" 
                                <?php if ($set['code'] == @$data['code']) { ?>
                                selected=""
                                <?php } ?>
                            ><?= $set['code'] ?> <?= $set['name'] ?></option>
                            <?php } ?>
                        </select>
                        <input 
                            type="text" 
                            name="phone" 
                            style="width: 50%; font-size: .75em;"
                            placeholder="<?= Translate::td('Phone, mobile (only digits)') ?>"
                            value="<?= @$data['phone'] ?>" 
                            autocomplete="nope" 
                            pattern="[0-9]{2,15}"
                        >-->
                        <span><?= Translate::td('Optional') ?></span>
                        <input type="text" name="referrer_code" value="<?= $referrer_code ?>" placeholder="<?= Translate::td('REFERRER CODE') ?> (<?= strtoupper(Translate::td('If available')) ?>)" autocomplete="nope">
                        <input type="password" name="password" pattern=".{6,120}" placeholder="<?= Translate::td('Password') ?>" autocomplete="new-password">
                        <span class="password-label"><?= Translate::td('Password must be more than 6 symbols') ?></span>
                        <div class="captcha">
                            <image src="data:image/png;base64,<?= $image ?>">
                                <div class="captcha-input">
                                    <p><?= Translate::td('Enter security code') ?></p>
                                    <input type="text" name="captcha" placeholder="Captcha" required>
                                </div>
                        </div>
                        <p class="terms_conditions">
                            <input type="checkbox" id="terms_conditions"/>
                            <label for="terms_conditions"><?= Translate::td('I agree to the terms and conditions') ?></label>
                        </p>
                        <div class="row center">
                            <button type="submit" id="btnRegistration" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                <?= Translate::td('Register') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * @param string $image
     * @param [] $data
     * @param string $error
     * @return string HTML
     */
    static public function registerForm2($image, $data = [], $error = '')
    {
        $referrer_code = '';
        if (isset($_GET['referrer_code'])) {
            $referrer_code = $_GET['referrer_code'];
        } else if (isset($data['referrer_code'])) {
            $referrer_code = $data['referrer_code'];
        }
        if ($referrer_code) {
            session_start();
            $_SESSION['registration_referrer_code'] = $referrer_code;
            session_write_close();
        } else if (isset($_SESSION['registration_referrer_code'])) {
            $referrer_code = $_SESSION['registration_referrer_code'];
        }
        $country_codes = json_decode(file_get_contents(PATH_TO_WEB_ROOT_DIR . '/scripts/phone_codes.json'), TRUE);
        ob_start();
        ?>
        <div class="row">
            <div class="col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <h3><?= Translate::td('Cryptaur registration') ?></h3>
                <div class="row">
                    <form class="registration col s12" action="<?= Investor_controller::REGISTER_URL ?>" method="post" autocomplete="off">
                        <?php if (isset($_GET['err'])) { ?>
                            <label class="red-text"><?= Translate::td('Error') ?> <?= $_GET['err'] ?>
                                : <?= Translate::td($_GET['err_text']) ?></label>
                        <?php } ?>
                        <?php if ($error) { ?>
                            <label class="red-text"><?= Translate::td('Error') ?>: <?= Translate::td($error) ?></label>
                        <?php } ?>
                        <input type="text" name="firstname" placeholder="<?= Translate::td('First name') ?>" value="<?= @$data['firstname'] ?>">
                        <input type="text" name="lastname" placeholder="<?= Translate::td('Last name') ?>" value="<?= @$data['lastname'] ?>">
                        <input type="email" name="email" placeholder="Email" value="<?= @$data['email'] ?>" autocomplete="nope">
                        <!--<select class="phone_code" name="code" required="">
                            <option disabled="" selected="">-</option>
                            <?php foreach ($country_codes as $set) { ?>
                            <option value="<?= $set['code'] ?>"><?= $set['code'] ?> <?= $set['name'] ?></option>
                            <?php } ?>
                        </select>
                        <input 
                            type="text" 
                            name="phone" 
                            style="width: 50%; font-size: .75em;"
                            placeholder="<?= Translate::td('Phone, mobile (only digits)') ?>"
                            value="<?= @$data['phone'] ?>" 
                            autocomplete="nope" 
                            pattern="[0-9]{2,15}"
                        >
                        <span><?= Translate::td('Use only numbers') ?></span>-->
                        <select class="select-wallet">
                            <option value="choose" disabled selected><?= Translate::td('Choose type wallet') ?></option>
                            <option value="inner-wallet"><?= Translate::td('Inner wallet') ?></option>
                            <option value="external-wallet"><?= Translate::td('External wallet') ?></option>
                        </select>
                        <div class="block_inner-wallet">
                            <input type="text" name="eth_address" placeholder="<?= Translate::td('ETH-ADDRESS') ?>" value="" autocomplete="nope">
                        </div>
                        <div class="block_external-wallet">
                            <input type="text" name="eth_address" placeholder="<?= Translate::td('ETH-ADDRESS') ?>" value="" autocomplete="nope">
                        </div>
                        <!-- Modal Structure -->
                        <div id="modal_external-wallet" class="modal">
                            <div class="modal-content">
                                <h4><?= Translate::td('Warning') ?></h4>
                                <p>
                                    <input type="checkbox" id="warning_1" class="warning-checkbox"/><label for="warning_1"><?= Translate::td('I control private key from the specified address') ?></label>
                                </p>
                                <p>
                                    <input type="checkbox" id="warning_2" class="warning-checkbox"/><label for="warning_2"><?= Translate::td('The specified address is not the depository address of crypto exchange') ?></label>
                                </p>
                                <p>
                                    <input type="checkbox" id="warning_3" class="warning-checkbox"/><label for="warning_3"><?= Translate::td('The specified address corresponds to a wallet that supports ERC20 token standard, for example, MyEtherWallet') ?></label>
                                </p>
                            </div>
                        </div>
                        <input type="text" name="referrer_code" value="<?= $referrer_code ?>" placeholder="<?= Translate::td('REFERRER CODE') ?>" autocomplete="nope">
                        <input type="password" name="password" pattern=".{6,120}" placeholder="<?= Translate::td('Password') ?>" autocomplete="new-password">
                        <span><?= Translate::td('Password must be more than 6 symbols') ?></span>
                        <br/>
                        <image src="data:image/png;base64,<?= $image ?>">
                            <input type="text" name="captcha" placeholder="Captcha" required>
                            <div class="row center">
                                <button type="submit" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                    <?= Translate::td('Register') ?>
                                </button>
                            </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    static public function recoverForm($image, $message = '')
    {
        ob_start();
        ?>
        <div class="row">
            <div class="col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <h3><?= Translate::td('Password recovery') ?></h3>
                <div class="row">
                    <form class="registration col s12" action="<?= Investor_controller::RECOVER_URL ?>" method="post" autocomplete="off">
                        <?php if (isset($_GET['err'])) { ?>
                            <label class="red-text"><?= Translate::td('Error') ?> <?= $_GET['err'] ?>
                                : <?= Translate::td($_GET['err_text']) ?></label>
                        <?php } ?>
                        <?php if ($message) { ?>
                            <label class="blue-text"><?= $message ?></label>
                        <?php } ?>
                        <input type="email" name="email" placeholder="Email" autocomplete="nope">
                        <div class="captcha">
                            <image src="data:image/png;base64,<?= $image ?>">
                                <div class="captcha-input">
                                    <p><?= Translate::td('Enter security code') ?></p>
                                    <input type="text" name="captcha" placeholder="Captcha" required>
                                </div>
                        </div>
                        <div class="row center">
                            <button type="submit" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                <?= Translate::td('Recover') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    static public function ethSetupForm()
    {
        ob_start();
        ?>
        <div class="row">
            <div class="col s12 m8 offset-m2 l6 offset-l3 center">
                <h5>
                    <?= Translate::td('Please wait for completion of registration your own Cryptaur Ether Wallet') ?>.
                </h5>
                <p><?= Translate::td('The page will automatically reboot after 30 seconds') ?></p>
            </div>
            <script>
                setTimeout(function () {
                    document.location.reload();
                }, 30000);
            </script>
        </div>
        <?php
        return ob_get_clean();
    }

    static public function ethSetupForm2()
    {
        ob_start();
        ?>
        <div class="row">
            <div class="col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <h3><?= Translate::td('User eth setup') ?></h3>
                <div class="row">
                    <form class="registration col s12" action="<?= Investor_controller::SET_EMPTY_ETH_ADDRESS ?>" method="post" autocomplete="off">
                        <?php if (isset($_GET['err'])) { ?>
                            <label class="red-text"><?= Translate::td('Error') ?> <?= $_GET['err'] ?>
                                : <?= Translate::td($_GET['err_text']) ?></label>
                        <?php } ?>

                        <select class="select-wallet">
                            <option value="choose" disabled selected><?= Translate::td('Choose type wallet') ?></option>
                            <option value="inner-wallet"><?= Translate::td('Inner wallet') ?></option>
                            <option value="external-wallet"><?= Translate::td('External wallet') ?></option>
                        </select>
                        <div class="block_inner-wallet">
                            <p><?= Translate::td('Eth address') ?>:</p>
                            <input type="text" name="eth_address" placeholder="<?= Translate::td('ETH-ADDRESS') ?>" value="<?= Application::$authorizedInvestor->eth_address ?>" autocomplete="nope" readonly class="eth_address">
                        </div>
                        <div class="block_external-wallet">
                            <p><?= Translate::td('Eth address') ?>:</p>
                            <input type="text" name="external_eth_address" placeholder="<?= Translate::td('ETH-ADDRESS') ?>" value="" autocomplete="nope">
                        </div>
                        <!-- Modal Structure -->
                        <div id="modal_external-wallet" class="modal">
                            <div class="modal-content">
                                <h4><?= Translate::td('Warning') ?></h4>
                                <p>
                                    <input type="checkbox" id="warning_1" class="warning-checkbox"/><label for="warning_1"><?= Translate::td('I control private key from the specified address') ?></label>
                                </p>
                                <p>
                                    <input type="checkbox" id="warning_2" class="warning-checkbox"/><label for="warning_2"><?= Translate::td('The specified address is not the depository address of crypto exchange') ?></label>
                                </p>
                                <p>
                                    <input type="checkbox" id="warning_3" class="warning-checkbox"/><label for="warning_3"><?= Translate::td('The specified address corresponds to a wallet that supports ERC20 token standard, for example, MyEtherWallet') ?></label>
                                </p>
                            </div>
                        </div>

                        <div class="row center">
                            <button type="submit" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                <?= Translate::td('Set') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    static private function tabsSettings($activeTabs)
    {
        ob_start();
        ?>
        <div class="row settings">
            <div class="container">
                <ul class="tabs-block col s12">
                    <li class="tab-element <?= $activeTabs == 'settings' ? 'active' : '' ?> col s6">
                        <a href="<?= Investor_controller::SETTINGS_URL ?>"><?= Translate::td('User settings') ?></a>
                    </li>
                    <li class="tab-element <?= $activeTabs == 'secondFactor' ? 'active' : '' ?> col s6">
                        <a href="<?= Investor_controller::SECONDFACTORSET_URL ?>"><?= Translate::td('Two-factor authentication settings') ?></a>
                    </li>
                </ul>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    static public function settings()
    {
        ob_start();
        ?>
        <?= self::tabsSettings('settings') ?>
        <div class="row">
            <div class="settings-block col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <form action="<?= Investor_controller::SETTINGS_URL ?>" method="post" autocomplete="off">
                    <h3><?= Translate::td('User settings') ?></h3>
                    <?php if (isset($_GET['password_err'])) { ?>
                        <label class="red-text"><?= Translate::td('Password must be more than 6 symbols') ?></label>
                    <?php } ?>
                    <?php if (isset($_GET['eth_address_err'])) { ?>
                        <label class="red-text"><?= Translate::td('not a valid eth address') ?></label>
                    <?php } ?>
                    <div class="row">
                        <?= Translate::td('Email') ?>: <strong><?= Application::$authorizedInvestor->email ?></strong>
                    </div>
                    <div class="row">
                        <?= Translate::td('First name') ?>:
                        <input type="text" name="firstname" placeholder="first name" value="<?= Application::$authorizedInvestor->firstname ?>" autocomplete="nope">
                    </div>
                    <div class="row">
                        <?= Translate::td('Last name') ?>:
                        <input type="text" name="lastname" placeholder="last name" value="<?= Application::$authorizedInvestor->lastname ?>" autocomplete="nope">
                    </div>
                    <div class="row">
                        <?= Translate::td('Referrer code') ?>:
                        <a href="<?= APPLICATION_URL . '/' . Investor_controller::REGISTER_URL . '?referrer_code=' . Application::$authorizedInvestor->referrer_code ?>">
                            <strong><?= Application::$authorizedInvestor->referrer_code ?></strong>
                        </a>
                    </div>
                    <div class="row">
                        <?= Translate::td('Password') ?> (<?= Translate::td('leave empty if not changing') ?>):
                        <input type="password" name="password" value="" pattern=".{6,120}" autocomplete="new-password">
                        <span>
                            <?= Translate::td('Password must be more than 6 symbols') ?>.
                            <?= Translate::td('Spaces not valid') ?>.
                        </span>
                    </div>
                    <button type="submit" class="waves-effect waves-light btn" style="width: 100%">
                        <?= Translate::td('Set') ?>
                    </button>
                </form>
                <br>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    static public function settings2()
    {
        $list2FA = variants_2FA::varList();
        ob_start();
        ?>
        <div class="row">
            <div class="settings-block col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <form action="<?= Investor_controller::SETTINGS_URL ?>" method="post" autocomplete="off">
                    <h3><?= Translate::td('User settings') ?></h3>
                    <?php if (isset($_GET['password_err'])) { ?>
                        <label class="red-text"><?= Translate::td('Password must be more than 6 symbols') ?></label>
                    <?php } ?>
                    <?php if (isset($_GET['eth_address_err'])) { ?>
                        <label class="red-text"><?= Translate::td('not a valid eth address') ?></label>
                    <?php } ?>
                    <div class="row">
                        <?= Translate::td('Email') ?>: <strong><?= Application::$authorizedInvestor->email ?></strong>
                    </div>
                    <div class="row">
                        <?= Translate::td('First name') ?>:
                        <input type="text" name="firstname" placeholder="first name" value="<?= Application::$authorizedInvestor->firstname ?>" autocomplete="nope">
                    </div>
                    <div class="row">
                        <?= Translate::td('Last name') ?>:
                        <input type="text" name="lastname" placeholder="last name" value="<?= Application::$authorizedInvestor->lastname ?>" autocomplete="nope">
                    </div>
                    <div class="row">
                        <?= Translate::td('Referrer code') ?>:
                        <a href="<?= APPLICATION_URL . '/' . Investor_controller::REGISTER_URL . '?referrer_code=' . Application::$authorizedInvestor->referrer_code ?>">
                            <strong><?= Application::$authorizedInvestor->referrer_code ?></strong>
                        </a>
                    </div>
                    <div class="row">
                        <select class="select-wallet">
                            <option value="inner-wallet" selected><?= Translate::td('Inner wallet') ?></option>
                            <option value="external-wallet"><?= Translate::td('External wallet') ?></option>
                        </select>
                        <div class="block_inner-wallet">
                            <p><?= Translate::td('Eth address') ?>:</p>
                            <input type="text" name="eth_address" placeholder="<?= Translate::td('ETH-ADDRESS') ?>" value="<?= Application::$authorizedInvestor->eth_address ?>" autocomplete="nope" readonly class="eth_address">
                        </div>
                        <div class="block_external-wallet">
                            <p><?= Translate::td('Eth address') ?>:</p>
                            <input type="text" name="external_eth_address" placeholder="<?= Translate::td('ETH-ADDRESS') ?>" value="" autocomplete="nope">
                        </div>
                        <!-- Modal Structure -->
                        <div id="modal_external-wallet" class="modal">
                            <div class="modal-content">
                                <h4><?= Translate::td('Warning') ?></h4>
                                <p>
                                    <input type="checkbox" id="warning_1" class="warning-checkbox"/><label for="warning_1"><?= Translate::td('I control private key from the specified address') ?></label>
                                </p>
                                <p>
                                    <input type="checkbox" id="warning_2" class="warning-checkbox"/><label for="warning_2"><?= Translate::td('The specified address is not the depository address of crypto exchange') ?></label>
                                </p>
                                <p>
                                    <input type="checkbox" id="warning_3" class="warning-checkbox"/><label for="warning_3"><?= Translate::td('The specified address corresponds to a wallet that supports ERC20 token standard, for example, MyEtherWallet') ?></label>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <?= Translate::td('Eth withdrawn') ?>:
                        <strong><?= Application::$authorizedInvestor->eth_withdrawn ?></strong>
                    </div>
                    <div class="row">
                        <?= Translate::td('Eth bounty') ?>:
                        <strong><?= Application::$authorizedInvestor->eth_bounty ?></strong>
                    </div>
                    <div class="row">
                        <?= Translate::td('Password') ?> (<?= Translate::td('leave empty if not changing') ?>):
                        <input type="password" name="password" value="" pattern=".{6,120}" autocomplete="new-password">
                        <span>
                            <?= Translate::td('Password must be more than 6 symbols') ?>.
                            <?= Translate::td('Spaces not valid') ?>.
                        </span>
                    </div>
                    <div class="row">
                        <?= Coin::token() ?>: <strong><?= Application::$authorizedInvestor->tokens_count ?></strong>
                    </div>
                    <button type="submit" class="waves-effect waves-light btn" style="width: 100%">
                        <?= Translate::td('Set') ?>
                    </button>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    static public function cryptauretherwallet()
    {
        $wallet = EtherWallet::getByInvestorId(Application::$authorizedInvestor->id);
        $sendIsEnabled = EthQueue::sendCptWalletIsOn() && EthQueue::sendEthWalletIsOn();

        ob_start();
        ?>

        <section class="ether-wallet">
            <div class="row tokens-info">
                <h3><?= Translate::td('TO SEE YOUR TOKENS IN YOUR OWN WALLET ENTER THE FOLLOWING VALUES') ?></h3>
                <div class="col s12 m12 l6 xl4">
                    <h4><?= Translate::td('Token Contract Address') ?></h4>
                    <h5><?= ETH_TOKENS_CONTRACT ?></h5>
                </div>
                <div class="col s12 m6 l3 xl4">
                    <h4><?= Translate::td('Token Symbol') ?></h4>
                    <h5><?= Coin::token() ?></h5>
                </div>
                <div class="col s12 m6 l3 xl4">
                    <h4><?= Translate::td('Decimal') ?></h4>
                    <h5>8</h5>
                </div>
            </div>
            <div class="row">
                <h3><?= Translate::td('Cryptaur Ether Wallet') ?></h3>
            </div>
            <div class="row">
                <?php
                foreach (EthQueue::getInvestorPendingDeposits(Application::$authorizedInvestor->id, [
                    EthQueue::TYPE_SENDETH_WALLET,
                    EthQueue::TYPE_SENDCPT_WALLET
                ]) as $element) {
                    ?>
                    <h5>
                        <?= Translate::td('Pending transaction') ?>
                        <?php
                        switch ($element->action_type) {
                            case EthQueue::TYPE_SENDETH_WALLET:
                                echo $element->data['eth'] . '&nbsp;ETH';
                                break;
                            case EthQueue::TYPE_SENDCPT_WALLET:
                                echo $element->data['cpt'] . '&nbsp;CPT';
                                break;
                            case EthQueue::TYPE_SENDPROOF_WALLET:
                                echo $element->data['proof'] . '&nbsp;PROOF';
                                break;
                        }
                        ?>
                        <?= date('Y-m-d H:i:s', $element->datetime) ?>&nbsp;UTC
                        <span style="font-size: 0.5em;">id: <?= $element->uuid ?></span>
                    </h5><br>
                    <?php
                }
                ?>
            </div>
            <div class="row">
                <form action="<?= EtherWallet_controller::SEND_WALLET ?>" method="post">
                    <div class="col s12 m7 l7">
                        <div class="input-field">
                            <p><?= Translate::td('To Address') ?></p>
                            <input type="text" name="address" class="address" value="" placeholder="0x3972ac286bd9db1456a5be259184dcfa6d9c2689">
                        </div>
                        <div class="input-field">
                            <p><?= Translate::td('Amount to Send') ?></p>
                            <input
                                    id="cryptaur_ether_wallet_amount_to_send"
                                    type="number" name="amount" value=""
                                    placeholder="<?= Translate::td('Amount') ?>"
                                    min="0" max="9999999999" step="0.00000001">
                            <select name="send_type" class="select-token">
                                <option value="ETH" class="default-option" selected>ETH</option>
                                <option value="CPT">CPT</option>
                                <option value="PROOF">PROOF</option>
                            </select>
                            <!-- <a href="#" onclick="return false;" class="disabled"><?= Translate::td('Send Entire Balance') ?></a> -->
                        </div>
                        <div id="cryptaur_ether_wallet_transaction_fee">
                            <p style="font-weight: normal;">
                                <?= Translate::td('Transaction fee') ?>:
                                <?= EthQueue::getFee() ?>
                                ETH,
                                <?= Translate::td('maximum amount') ?>:
                                <?php
                                $maximumAmount = Utility::floor_prec($wallet->eth - EthQueue::getFee(), 5);
                                if ($maximumAmount < 0) {
                                    $maximumAmount = 0;
                                }
                                ?>
                                <span id="cryptaur_ether_wallet_maximum_amount">
                                <?= number_format($maximumAmount, 8, '.', '') ?>
                                </span>
                                ETH
                            </p>
                        </div>
                        <div id="warning-wallet">
                            <p>
                                <input type="checkbox" id="warning_1" class="warning-checkbox"/><label for="warning_1"><?= Translate::td('I confirm that the specified destination address matches the wallet that supports the ERC20 standard') ?></label>
                            </p>
                            <p>
                                <input type="checkbox" id="warning_2" class="warning-checkbox"/><label for="warning_2"><?= Translate::td('I confirm that the indicated address is not the depository address of the exchange') ?></label>
                            </p>
                        </div>
                        <div class="input-field">
                            <div id="warning-minimum-amount">
                                <p><?= Translate::td('The minimal contibution in CPT tokens to the PROVER project is 5000 CPT') ?></p>
                            </div>
                            <button id="cryptaur_ether_wallet_send" <?= $sendIsEnabled ? '' : 'disabled' ?> class="waves-effect waves-light btn btn-generate-transaction"><?= Translate::td('Send') ?></button>
                            <?php if (!$sendIsEnabled) { ?>
                                <p class="grey-text"><?= Translate::td('Cryptaur Ether Waller send functions temporary is off') ?></p>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="personal-data col s12 m5 l5">
                        <?php if ($wallet->eth_address) { ?>
                            <p><?= Translate::td('Account Address') ?></p>
                            <p class="account-address">
                                <?= $wallet->eth_address ?>
                            </p>
                            <p><?= Translate::td('Account Balance') ?></p>
                            <p class="account-balance"><?= number_format($wallet->eth, 8, '.', '') ?> ETH</p>
                            <p class="account-balance"><?= number_format($wallet->cpt, 8, '.', '') ?> CPT</p>
                            <p class="account-balance"><?= number_format($wallet->proof, 8, '.', '') ?> PROOF</p>
                        <?php } else { ?>
                            <p><?= Translate::td('Account Address') ?></p>
                            <p class="account-address red"><?= Translate::td('Temporary unavailable') ?></p>
                            <p><?= Translate::td('Account Balance') ?></p>
                            <p class="account-balance">??? ETH</p>
                            <p class="account-balance">??? CPT</p>
                            <p class="account-balance">??? PROOF</p>
                        <?php } ?>
                        <p><?= Translate::td('Transaction History') ?></p>
                        <a target="_blank" href="https://etherscan.io/address/<?= $wallet->eth_address ?>">
                            ETH (https://etherscan.io)
                        </a><br>
                        <a target="_blank" href="https://ethplorer.io/address/<?= $wallet->eth_address ?>">
                            Tokens (Ethplorer.io)
                        </a>
                    </div>
                </form>
            </div>
        </section>

        <?php
        return ob_get_clean();
    }

    static public function invite_friends($message = '')
    {
        ob_start();
        ?>
        <div class="row">
            <div class="col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <h3><?= Translate::td('Invite friend') ?></h3>
                <div class="row">
                    <form class="login col s12" action="<?= Investor_controller::INVITE_FRIENDS_URL ?>" method="post" autocomplete="off">
                        <?php if (isset($_GET['err'])) { ?>
                            <label class="red-text"><?= Translate::td('Error') ?> <?= $_GET['err'] ?>
                                : <?= $_GET['err_text'] ?></label>
                        <?php } ?>
                        <?php if ($message) { ?>
                            <label class="blue-text"><?= $message ?></label>
                        <?php } ?>
                        <input type="email" name="email" placeholder="<?= Translate::td('Friend email') ?>">
                        <div class="row center">
                            <button type="submit" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                <?= Translate::td('Send') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

}