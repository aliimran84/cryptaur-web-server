<?php

namespace core\views;

use core\translate\Translate;
use core\engine\Router;

class SFchecker_view
{
    static public function secondfactorForm($url, $method, $message = '')
    {
        ob_start();
        ?>
        <div class="row">
            <div class="col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <h3><?= Translate::td('Two-Factor Authentication') ?></h3>
                <div class="row">
                    <form class="login col s12" action="<?= $url ?>" method="post" autocomplete="off">
                        <h5>
                            <?= Translate::td('You have two-factor authentication enabled so you must verify login') ?>.<br/>
                            <?= Translate::td("Click button to send the code") ?>
                        </h5>
                        <?php if ($message) { ?>
                            <label class="blue-text"><?= $message ?></label>
                        <?php } ?>
                        <?php if (isset($_REQUEST['sent'])) { ?>
                            <input type="text" name="otp" placeholder="<?= Translate::td('Authentication code') ?>">
                            <div class="row center">
                                <button type="submit" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                    <?= Translate::td('Verify') ?>
                                </button>
                            </div>
                        <?php } ?>
                        <div class="row center">
                            <?php if ($method == Router::GET_METHOD) { ?>
                            <a href="<?= $url ?>?sent=1" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                <?= isset($_GET['sent']) ? Translate::td('Re-send') : Translate::td('Sen') ?>
                            </a>
                            <?php } else { ?>
                            <button type="submit" name="sent" value="1" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                <?= isset($_POST['sent']) ? Translate::td('Re-send') : Translate::td('Send') ?>
                            </button>
                            <?php } ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    static public function secondfactorDualForm($url, $method, $message = '')
    {
        ob_start();
        ?>
        <div class="row">
            <div class="col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <h3><?= Translate::td('Two-Factor Authentication') ?></h3>
                <div class="row">
                    <form class="login col s12" action="<?= $url ?>" method="post" autocomplete="off">
                        <h5>
                            <?= Translate::td('You have two-factor authentication enabled so you must verify login') ?>.<br/>
                            <?= Translate::td("Click button to send the codes") ?>
                        </h5>
                        <?php if ($message) { ?>
                            <label class="blue-text"><?= $message ?></label>
                        <?php } ?>
                        <h5><?= Translate::td('Code from SMS') ?>:</h5>
                        <input type="text" name="code_1" placeholder="<?= Translate::td('Authentication code') ?>">
                        <h5><?= Translate::td('Code from email') ?>:</h5>
                        <?php if (isset($_REQUEST['sent'])) { ?>
                            <input type="text" name="code_2" placeholder="<?= Translate::td('Authentication code') ?>">
                            <div class="row center">
                                <button type="submit" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                    <?= Translate::td('Verify') ?>
                                </button>
                            </div>
                        <?php } ?>
                        <div class="row center">
                            <?php if ($method == Router::GET_METHOD) { ?>
                            <a href="<?= $url ?>?sent=1" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                <?= isset($_GET['sent']) ? Translate::td('Re-send') : Translate::td('Send') ?>
                            </a>
                            <?php } else { ?>
                            <button type="submit" name="sent" value="1" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                <?= isset($_POST['sent']) ? Translate::td('Re-send') : Translate::td('Send') ?>
                            </button>
                            <?php } ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

}