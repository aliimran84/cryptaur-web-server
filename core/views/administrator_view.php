<?php

namespace core\views;

use core\controllers\Administrator_controller;
use core\models\Administrator;
use core\models\Coin;
use core\models\PaymentServer;

class Administrator_view
{
    static public function loginForm()
    {
        ob_start();
        ?>
        <div class="row">
            <div class="col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <h3 class="red-text">Cryptaur administrator login</h3>
                <div class="row">
                    <form class="login col s12" action="<?= Administrator_controller::LOGIN_URL ?>" method="post">
                        <input type="text" name="email" placeholder="E-MAIL">
                        <input type="password" name="password" placeholder="PASSWORD">
                        <div class="row center">
                            <button type="submit" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                LOGIN
                            </button>
                            <!--<p>Forgot your account password? <a href="#">Recover</a></p>-->
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    static public function setForm($email)
    {
        ob_start();
        ?>
        <div class="row">
            <div class="col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <h3>Cryptaur admin settings</h3>
                <div class="row">
                    <form class="registration col s12" action="<?= Administrator_controller::SET_URL ?>" method="post">
                        <?php if (isset($_GET['err'])) {
                            // todo: decode error
                            ?>
                            <label>Error <?= $_GET['err'] ?></label>
                        <?php } ?>
                        <input type="text" name="email" placeholder="E-MAIL"
                            <?= $email ? 'readonly' : '' ?> value="<?= $email ?>">
                        <input type="password" name="password" placeholder="PASSWORD">
                        <div class="row center">
                            <button type="submit" class="waves-effect waves-light btn btn-send" style="width: 100%">
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

    static public function administratorsList()
    {
        ob_start();
        ?>
        <div class="row">
            <div class="col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <h3>Cryptaur administators list</h3>
                <?php foreach (Administrator::getAll() as $admin) { ?>
                    <a href="<?= Administrator_controller::SET_URL ?>?id=<?= $admin->id ?>"><?= $admin->email ?></a><br>
                <?php } ?>
                <br>
                <a href="<?= Administrator_controller::SET_URL ?>">+ new</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    static public function coinsSettings()
    {
        $paymentServer = PaymentServer::getFirst();
        ob_start();
        ?>
        <div class="row">
            <div class="col s12 m6 xl4">
                <?= PaymentServer_view::setForm($paymentServer) ?>
            </div>
            <div class="col s12 m6 xl4">
                <?= Coin_view::setRatesForm() ?>
            </div>
            <div class="col s12 m6 xl4">
                <?= Deposit_view::setMinimalsForm() ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}