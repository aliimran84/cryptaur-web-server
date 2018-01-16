<?php

namespace core\views;

use core\controllers\Investor_controller;
use core\engine\Application;
use core\models\Coin;
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

    static public function secondfactorForm($message = '')
    {
        ob_start();
        ?>
        <div class="row">
            <div class="col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <h3><?= Translate::td('Second Factor Authentication') ?></h3>
                <div class="row">
                    <form class="login col s12" action="<?= Investor_controller::SECONDFACTOR_URL ?>" method="post" autocomplete="off">
                        <?php if (isset($_GET['err'])) { ?>
                            <label class="red-text"><?= Translate::td('Error') ?> <?= $_GET['err'] ?>
                                : <?= Translate::td($_GET['err_text']) ?></label>
                        <?php } ?>
                        <?php if ($message) { ?>
                            <label class="blue-text"><?= $message ?></label>
                        <?php } ?>
                        <h5><?= Translate::td('Authentication code has been sended using preferred method') ?></h5>
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
                        <input type="text" name="eth_address" placeholder="<?= Translate::td('ETH-ADDRESS') ?>" value="<?= @$data['eth_address'] ?>" autocomplete="nope">
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
            <div class="col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <h3><?= Translate::td('Investor eth setup') ?></h3>
                <div class="row">
                    <form class="registration col s12" action="<?= Investor_controller::SET_EMPTY_ETH_ADDRESS ?>" method="post" autocomplete="off">
                        <?php if (isset($_GET['err'])) { ?>
                            <label class="red-text"><?= Translate::td('Error') ?> <?= $_GET['err'] ?>
                                : <?= Translate::td($_GET['err_text']) ?></label>
                        <?php } ?>
                        <input type="text" name="eth_address" placeholder="<?= Translate::td('ETH-ADDRESS') ?>" autocomplete="nope">
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
                    <h3><?= Translate::td('Investor settings') ?></h3>
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
                        <strong><?= Application::$authorizedInvestor->referrer_code ?></strong>
                    </div>
                    <div class="row">
                        <select class="select-wallet">
                            <option value="inner-wallet" selected><?= Translate::td('Inner wallet') ?></option>
                            <option value="external-wallet"><?= Translate::td('External wallet') ?></option>
                        </select>
                        <div class="block_inner-wallet">
                            <p><?= Translate::td('Eth address') ?>:</p>
                            <input type="text" name="eth_address" placeholder="eth-address" value="<?= Application::$authorizedInvestor->eth_address ?>" autocomplete="nope" readonly class="eth_address">
                        </div>
                        <!-- Modal Structure -->
                        <div id="modal_external-wallet" class="modal">
                            <div class="modal-content">
                                <h4><?= Translate::td('Warning') ?></h4>
                                <ul>
                                    <li><i class="large material-icons">check</i><?= Translate::td('You control private key from the specified address') ?></li>
                                    <li><i class="large material-icons">check</i><?= Translate::td('The specified address is not the depository address of crypto exchange') ?></li>
                                    <li><i class="large material-icons">check</i><?= Translate::td('The specified address corresponds to a wallet that supports ERC20 token standard, for example, MyEtherWallet') ?></li>
                                </ul>
                            </div>
                        </div>
                        <div class="block_external-wallet">
                            <p><?= Translate::td('Eth address') ?>:</p>
                            <input type="text" name="external_eth_address" placeholder="eth-address" value="<?= Application::$authorizedInvestor->eth_address ?>" autocomplete="nope">
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
                    <?php if (USE_2FA == TRUE): ?>
                    <div class="row">
                        <?= Translate::td('Preferred second factor authentication method') ?>:
                        <select name="2fa_method">
                            <option 
                                <?php if (Application::$authorizedInvestor->preferred_2fa == ""): ?>
                                selected="" 
                                <?php endif; ?>
                                value="NULL"
                            >
                                <?= Translate::td('Do not use') ?>
                            </option>
                            <option 
                                <?php if (Application::$authorizedInvestor->preferred_2fa == \core\gauthify\variants_2FA::email): ?>
                                selected="" 
                                <?php endif; ?>
                                value="<?= \core\gauthify\variants_2FA::email ?>"
                            >
                                <?= \core\gauthify\variants_2FA::email ?>
                            </option>
                            <!--<option 
                                <?php if (Application::$authorizedInvestor->preferred_2fa == \core\gauthify\variants_2FA::sms): ?>
                                selected="" 
                                <?php endif; ?>
                                value="<?= \core\gauthify\variants_2FA::sms ?>"
                            >
                                <?= \core\gauthify\variants_2FA::sms ?>
                            </option>-->
                        </select>
                    </div>
                    <?php endif; ?>
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

    static public function myetherwallet()
    {
        ob_start();
        ?>

        <section class="ether-wallet">
            <div class="row">
                <h3><?= Translate::td('My Ether Wallet') ?></h3>
            </div>
            <div class="row">
                <form action="#" method="post">
                    <div class="col s12 m7 l7">
                        <div class="input-field">
                            <p><?= Translate::td('To Address') ?></p>
                            <input type="text" name="address" value="" placeholder="0x2fd14b9a081b3d7b55348b32fb3b4f02431ad544">
                        </div>
                        <div class="input-field">
                            <p><?= Translate::td('Amount to Send') ?></p>
                            <input type="number" name="amount" value="" placeholder="<?= Translate::td('Amount') ?>">
                            <select>
                                <option value="ETH" selected>ETH</option>
                                <option value="CPT">CPT</option>
                            </select>
                            <a href="#"><?= Translate::td('Send Entire Balance') ?></a>
                        </div>
                        <div class="input-field">
                            <button class="waves-effect waves-light btn btn-generate-transaction"><?= Translate::td('Send') ?></button>
                        </div>
                    </div>
                    <div class="personal-data col s12 m5 l5">
                        <p><?= Translate::td('Account Address') ?></p>
                        <p class="account-address">0x2fd14b9a081b3d7b55348b32fb3b4f02431ad544</p>
                        <p><?= Translate::td('Account Balance') ?></p>
                        <p class="account-balance">0 ETH</p>
                        <p class="account-balance">0 CPT</p>
                        <p><?= Translate::td('Transaction History') ?></p>
                        <a href="https://etherscan.io/address/0x2fd14b9a081b3d7b55348b32fb3b4f02431ad544">ETH (https://etherscan.io)</a><br>
                        <a href="https://ethplorer.io/address/0x2fd14b9a081b3d7b55348b32fb3b4f02431ad544">Tokens (Ethplorer.io)</a>
                        <div class="buy-eth">
                            <div class="row">
                                <div class="coinbase-block col s12">
                                    <p class="coinbase">coinbase</p>
                                    <a href class="waves-effect waves-light btn btn-price">1 ETH = 1300.00 USD</a>
                                </div>
                            </div>
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