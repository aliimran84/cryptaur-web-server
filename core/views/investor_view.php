<?php

namespace core\views;

use core\controllers\Investor_controller;
use core\controllers\EtherWallet_controller;
use core\engine\Application;
use core\models\Coin;
use core\models\EtherWallet;
use core\models\EthQueue;
use core\secondfactor\variants_2FA;
use core\translate\Translate;

class Investor_view
{
    static public function loginForm($message = '')
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
        $list2FA = variants_2FA::varList();
        ob_start();
        ?>
        <div class="row">
            <div class="settings-block col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <form action="<?= Investor_controller::SECONDFACTORSET_URL ?>" method="post" autocomplete="off">
                    <h3><?= Translate::td('Two-factor authentication settings') ?></h3>
                    <h5 class="blue-text"><?= Translate::td('You must choose two-factor authentication option') ?></h5>
                    <?php if (isset($_GET['phone_req_err'])) { ?>
                        <label class="red-text"><?= Translate::td('You cannot select SMS-based second factor authentication methods without verified phone number') ?></label>
                    <?php } ?>
                    <?php if (isset($_GET['send_sms_err'])) { ?>
                        <label class="red-text"><?= Translate::td('Unable to sent SMS, service temporary disabled') ?></label>
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
                            <?php foreach ($list2FA AS $var) {
                                if ($var == variants_2FA::both) continue;
                                if ($var == variants_2FA::sms) continue;
                                ?>
                                <option
                                    <?php if (
                                        Application::$authorizedInvestor->preferred_2fa == $var
                                        || (Application::$authorizedInvestor->preferred_2fa == "" && $var == variants_2FA::sms)
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
                            && Application::$authorizedInvestor->preferred_2fa != variants_2FA::both
                        ) { ?>
                            style="display:none"
                        <?php } ?>
                    >
                        <?= Translate::td('Phone, mobile') ?>:
                        <input
                                type="text"
                                name="phone"
                                placeholder="phone, mobile"
                                value="<?= \core\engine\Utility::clear_except_numbers(Application::$authorizedInvestor->phone) ?>"
                                autocomplete="nope"
                        >
                        <span><?= Translate::td('e.g. 79997774433') ?></span>
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

    static public function secondfactorForm($message = '')
    {
        ob_start();
        ?>
        <div class="row">
            <div class="col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <h3><?= Translate::td('Two-Factor Authentication') ?></h3>
                <div class="row">
                    <form class="login col s12" action="<?= Investor_controller::SECONDFACTOR_URL ?>" method="post" autocomplete="off">
                        <?php if (isset($_GET['err'])) { ?>
                            <label class="red-text"><?= Translate::td('Error') ?> <?= $_GET['err'] ?>
                                : <?= Translate::td($_GET['err_text']) ?></label>
                        <?php } ?>
                        <?php if ($message) { ?>
                            <label class="blue-text"><?= $message ?></label>
                        <?php } ?>
                        <h5><?= Translate::td('Authentication code has been sent using preferred method') ?></h5>
                        <input type="password" name="otp" placeholder="<?= Translate::td('Authentication code') ?>" autocomplete="new-password">
                        <div class="row center">
                            <button type="submit" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                <?= Translate::td('Login') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    static public function secondfactorDualForm($message = '')
    {
        ob_start();
        ?>
        <div class="row">
            <div class="col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <h3><?= Translate::td('Two-Factor Authentication') ?></h3>
                <div class="row">
                    <form class="login col s12" action="<?= Investor_controller::SECONDFACTORDUAL_URL ?>" method="post" autocomplete="off">
                        <?php if (isset($_GET['err'])) { ?>
                            <label class="red-text"><?= Translate::td('Error') ?> <?= $_GET['err'] ?>
                                : <?= Translate::td($_GET['err_text']) ?></label>
                        <?php } ?>
                        <?php if ($message) { ?>
                            <label class="blue-text"><?= $message ?></label>
                        <?php } ?>
                        <h5><?= Translate::td('Code from SMS') ?>:</h5>
                        <input type="password" name="code_1" placeholder="<?= Translate::td('Authentication code') ?>" autocomplete="new-password">
                        <h5><?= Translate::td('Code from email') ?>:</h5>
                        <input type="password" name="code_2" placeholder="<?= Translate::td('Authentication code') ?>" autocomplete="new-password">
                        <div class="row center">
                            <button type="submit" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                <?= Translate::td('Login') ?>
                            </button>
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
                        <input type="password" name="otp" placeholder="<?= Translate::td('Authentication code') ?>" autocomplete="new-password">
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
     * @param [] $data
     * @param string $error
     * @return string HTML
     */
    static public function registerForm($data = [], $error = '')
    {
        $referrer_code = '';
        if (isset($_GET['referrer_code'])) {
            $referrer_code = $_GET['referrer_code'];
        } else if (isset($data['referrer_code'])) {
            $referrer_code = $data['referrer_code'];
        }
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
                        <input type="text" name="referrer_code" value="<?= $referrer_code ?>" placeholder="<?= Translate::td('REFERRER CODE') ?>" autocomplete="nope">
                        <input type="password" name="password" pattern=".{6,120}" placeholder="<?= Translate::td('Password') ?>" autocomplete="new-password">
                        <span><?= Translate::td('Password must be more than 6 symbols') ?></span>
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

    /**
     * @param [] $data
     * @param string $error
     * @return string HTML
     */
    static public function registerForm2($data = [], $error = '')
    {
        $referrer_code = '';
        if (isset($_GET['referrer_code'])) {
            $referrer_code = $_GET['referrer_code'];
        } else if (isset($data['referrer_code'])) {
            $referrer_code = $data['referrer_code'];
        }
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

    static public function recoverForm($message = '')
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

    static public function settings()
    {
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
                <a href="<?= Investor_controller::SECONDFACTORSET_URL ?>"><?= Translate::td('Two-factor authentication settings') ?></a>
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
                <form action="<?= EtherWallet_controller::SEND_WALLET ?>" method="post">
                    <div class="col s12 m7 l7">
                        <div class="input-field">
                            <p><?= Translate::td('To Address') ?></p>
                            <input type="text" name="address" class="address" value="" placeholder="0xDE2C06c6e48CD98f8977794c2f7aa5Eeb93b95f9">
                        </div>
                        <div class="input-field">
                            <p><?= Translate::td('Amount to Send') ?></p>
                            <input
                                    type="number" name="amount" value=""
                                    placeholder="<?= Translate::td('Amount') ?>"
                                    min="0" max="9999999999" step="0.00000001">
                            <select name="send_type" class="select-token">
                                <option value="ETH" class="default-option" selected>ETH</option>
                                <option value="CPT">CPT</option>
                            </select>
                            <!-- <a href="#" onclick="return false;" class="disabled"><?= Translate::td('Send Entire Balance') ?></a> -->
                        </div>
                        <div class="input-field">

                            <button <?= $sendIsEnabled ? '' : 'disabled' ?> class="waves-effect waves-light btn btn-generate-transaction"><?= Translate::td('Send') ?></button>
                            <?php if (!$sendIsEnabled) { ?>
                                <p class="grey-text"><?= Translate::td('Cryptaur Ether Waller send functions temporary is off') ?></p>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="personal-data col s12 m5 l5">
                        <p><?= Translate::td('Account Address') ?></p>
                        <p class="account-address"><?= $wallet->eth_address ?></p>
                        <p><?= Translate::td('Account Balance') ?></p>
                        <p class="account-balance"><?= number_format($wallet->eth, 8, '.', '') ?> ETH</p>
                        <p class="account-balance"><?= number_format($wallet->cpt, 8, '.', '') ?> CPT</p>
                        <p><?= Translate::td('Transaction History') ?></p>
                        <a href="https://etherscan.io/address/<?= $wallet->eth_address ?>">
                            ETH (https://etherscan.io)
                        </a><br>
                        <a href="https://ethplorer.io/address/<?= $wallet->eth_address ?>">
                            Tokens (Ethplorer.io)
                        </a>
                    </div>
                    <!-- Modal Structure -->
                    <div id="modal_warning-wallet" class="modal">
                        <div class="modal-content">
                            <h4><?= Translate::td('Warning') ?></h4>
                            <p>
                                <input type="checkbox" id="warning_1" class="warning-checkbox"/><label for="warning_1"><?= Translate::td('I confirm that the specified destination address matches the wallet that supports the ERC20 standard') ?></label>
                            </p>
                            <p>
                                <input type="checkbox" id="warning_2" class="warning-checkbox"/><label for="warning_2"><?= Translate::td('I confirm that the indicated address is not the depository address of the exchange') ?></label>
                            </p>
                        </div>
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