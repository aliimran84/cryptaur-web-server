<?php

namespace core\views;

use core\controllers\Investor_controller;
use core\engine\Application;
use core\models\Coin;

class Investor_view
{
    static public function loginForm()
    {
        ob_start();
        ?>
        <div class="row">
            <div class="col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <h3>Cryptaur login</h3>
                <div class="row">
                    <form class="login col s12" action="<?= Investor_controller::LOGIN_URL ?>" method="post">
                        <?php if (isset($_GET['err'])) { ?>
                            <label class="red-text">Error <?= $_GET['err'] ?>: <?= $_GET['err_text'] ?></label>
                        <?php } ?>
                        <input type="text" name="email" placeholder="E-MAIL">
                        <input type="password" name="password" placeholder="PASSWORD">
                        <div class="row center">
                            <button type="submit" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                LOGIN
                            </button>
                            <!--<p>Forgot your account password? <a href="#">Recover</a></p>-->
                        </div>
                        <h5>Not a member yet?</h5>
                        <div class="row center">
                            <a href="<?= Investor_controller::REGISTER_URL ?>" class="waves-effect waves-light btn btn-login">Register</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    static public function registerForm()
    {
        ob_start();
        ?>
        <div class="row">
            <div class="col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <h3>Cryptaur registration</h3>
                <div class="row">
                    <form class="registration col s12" action="<?= Investor_controller::REGISTER_URL ?>" method="post">
                        <?php if (isset($_GET['err'])) { ?>
                            <label class="red-text">Error <?= $_GET['err'] ?>: <?= $_GET['err_text'] ?></label>
                        <?php } ?>
                        <input type="text" name="email" placeholder="E-MAIL">
                        <input type="password" name="password" placeholder="PASSWORD">
                        <input type="text" name="eth_address" placeholder="ETH-ADDRESS">
                        <input type="text" name="referrer_code" placeholder="REFERRER CODE">
                        <div class="row center">
                            <button type="submit" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                Register
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
                <h3>Investor eth setup</h3>
                <div class="row">
                    <form class="registration col s12" action="<?= Investor_controller::SET_ETH_ADDRESS ?>" method="post">
                        <input type="text" name="eth_address" placeholder="ETH-ADDRESS">
                        <div class="row center">
                            <button type="submit" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                Set
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
                <h3>Investor settings</h3>
                <div class="row">
                    Email: <strong><?= Application::$authorizedInvestor->email ?></strong>
                </div>
                <div class="row">
                    Referrer code: <strong><?= Application::$authorizedInvestor->referrer_code ?></strong>
                </div>
                <div class="row">
                    Eth address: <strong><?= Application::$authorizedInvestor->eth_address ?></strong>
                </div>
                <div class="row">
                    Eth withdrawn: <strong><?= Application::$authorizedInvestor->eth_withdrawn ?></strong>
                </div>
                <div class="row">
                    Eth bounty: <strong><?= Application::$authorizedInvestor->eth_bounty ?></strong>
                </div>
                <div class="row">
                    <?= Coin::token() ?>: <strong><?= Application::$authorizedInvestor->tokens_count ?></strong>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}