<?php

namespace core\views;

use core\controllers\Administrator_controller;
use core\models\Administrator;
use core\models\Coin;
use core\models\PaymentServer;
use core\translate\Translate;

class Administrator_view
{
    static public function loginForm()
    {
        ob_start();
        ?>
        <div class="row">
            <div class="col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <h3 class="red-text"><?= Translate::td('Cryptaur administrator login') ?></h3>
                <div class="row">
                    <form class="login col s12" action="<?= Administrator_controller::LOGIN_URL ?>" method="post" autocomplete="off">
                        <input type="text" name="email" placeholder="E-MAIL">
                        <input type="password" name="password" placeholder="<?= Translate::td('Password') ?>" autocomplete="new-password">
                        <div class="row center">
                            <button type="submit" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                <?= Translate::td('Login') ?>
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
                <h3><?= Translate::td('Cryptaur admin settings') ?></h3>
                <div class="row">
                    <form class="registration col s12" action="<?= Administrator_controller::SET_URL ?>" method="post" autocomplete="off">
                        <?php if (isset($_GET['err'])) {
                            // todo: decode error
                            ?>
                            <label><?= Translate::td('Error') ?> <?= $_GET['err'] ?></label>
                        <?php } ?>
                        <input type="text" name="email" placeholder="E-MAIL"
                            <?= $email ? 'readonly' : '' ?> value="<?= $email ?>">
                        <input type="password" name="password" placeholder="<?= Translate::td('Password') ?>" autocomplete="new-password">
                        <div class="row center">
                            <button type="submit" class="waves-effect waves-light btn btn-send" style="width: 100%">
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

    static public function administratorsList()
    {
        ob_start();
        ?>
        <div class="row">
            <div class="col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <h3><?= Translate::td('Cryptaur administators list') ?></h3>
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
            <div class="col s12 m6 xl4">
                <?= Deposit_view::setPersmissionsForm() ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    static public function logsList()
    {
        ob_start();
        $dataLogPHP = Administrator::getLogsPHP();
        $dataLogMySQL = Administrator::getLogsMySQL();
        $alarmMessages = Administrator::getAlarmMessage(200);
        ?>
        <div class="row">
            <ul class="tabs">
                <li class="tab col s4">
                    <a class="active" href="#alarmMessages"><?= Translate::td('Alarm messages') ?></a></li>
                <li class="tab col s4">
                    <a href="#logPHP">PHP</a>
                </li>
                <li class="tab col s4">
                    <a href="#logMySQL">MySQL</a>
                </li>
            </ul>
            <div id="alarmMessages" class="col s12">
                <ul>
                    <?php foreach ($alarmMessages as $alarmMessage) : ?>
                        <li>
                            <form method="post" action="<?= Administrator_controller::LOGS ?>#alarmMessages" autocomplete="off">
                                <input name="id" type="number" class="message-id" value="<?= $alarmMessage['id'] ?>">
                                <div class="col s12 m10">
                                    <p><?= $alarmMessage['message'] ?></p>
                                </div>
                                <div class="col s12 m2">
                                    <button type="submit" class="waves-effect waves-light btn btn-send"><?= Translate::td('Delete') ?></button>
                                </div>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div id="logPHP" class="col s12" style="display: none;">
                <ul>
                    <?php for ($i = count($dataLogPHP) - 1; $i >= count($dataLogPHP) - 1000; $i--) : ?>
                        <li>
                            <?= @$dataLogPHP[$i]; ?>
                        </li>
                    <?php endfor; ?>
                </ul>
            </div>
            <div id="logMySQL" class="col s12" style="display: none;">
                <ul>
                    <?php for ($i = count($dataLogMySQL) - 3000 >= 0 ? count($dataLogMySQL) - 3000 : 0; $i < count($dataLogMySQL); $i++) : ?>
                        <li>
                            <?= @$dataLogMySQL[$i]; ?>
                        </li>
                    <?php endfor; ?>
                </ul>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    static public function ratesCPLList()
    {
        ob_start();
        $rates = Administrator::getCPLRates();
        ?>
        <div class="row">
            <div class="col s12">
                <table>
                    <thead>
                        <tr>
                            <th data-field="rate">Rate</th>
                            <th data-field="datetime">DateTime</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rates as $rate) : ?>
                        <tr>
                            <td>
                                <?= $rate['rate'] ?>
                            </td>
                            <td>
                                <?= $rate['datetime'] ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    static public function ratesList()
    {
        ob_start();
        $rates = Administrator::getRates();
        ?>
        <div class="row">
            <div class="col s12">
                <table>
                    <thead>
                        <tr>
                            <th data-field="coin">Coin</th>
                            <th data-field="rate">Rate</th>
                            <th data-field="datetime">DateTime</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rates as $rate) : ?>
                        <tr>
                            <td>
                                <?= $rate['coin'] ?>
                            </td>
                            <td>
                                <?= $rate['rate'] ?>
                            </td>
                            <td>
                                <?= $rate['datetime'] ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}