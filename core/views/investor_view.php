<?php

namespace core\views;

use core\models\Investor_controller;

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
                        <input type="text" name="email" placeholder="E-MAIL">
                        <input type="password" name="password" placeholder="PASSWORD">
                        <div class="row">
                            <input type="submit" class="waves-effect waves-light btn btn-login" value="LOGIN">
                            <p>Forgot your account password? <a href="#">Recover</a></p>
                        </div>
                        <h5>Not a member yet?</h5>
                        <div class="row">
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
                    <form class="registration col s12" action="/<?= Investor_controller::REGISTER_URL ?>" method="post">
                        <?php if (isset($_GET['err'])) {
                            // todo: decode error
                            ?>
                            <label>Error <?= $_GET['err'] ?></label>
                        <?php } ?>
                        <input type="text" name="email" placeholder="E-MAIL">
                        <input type="password" name="password" placeholder="PASSWORD">
                        <input type="text" name="eth_address" placeholder="ETH-ADDRESS">
                        <input type="text" name="referrer_code" placeholder="REFERRER CODE">
                        <div class="row center">
                            <input type="submit" class="waves-effect waves-light btn btn-send" value="Register">
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}