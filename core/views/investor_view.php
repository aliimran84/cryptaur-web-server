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
                        <h5><?= Translate::td('Authentication code has been sended to your email') ?></h5>
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
            <div class="col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <form action="<?= Investor_controller::SETTINGS_URL ?>" method="post" autocomplete="off">
                    <h3><?= Translate::td('Investor settings') ?></h3>
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
                        <?= Translate::td('Eth address') ?>:
                        <strong><?= Application::$authorizedInvestor->eth_address ?></strong>
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
                        <span><?= Translate::td('Password must be more than 6 symbols') ?></span>
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