<?php

namespace core\views;

class SFchecker_view
{
    static public function secondfactorForm($url, $message = '')
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
                            <?= Translate::td("Click 'Sent' button to sent the code") ?>
                        </h5>
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
                            <a href="<?= $url ?>?sent=1" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                <?= isset($_GET['sent']) ? Translate::td('Re-sent') : Translate::td('Sent') ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    static public function secondfactorDualForm($url, $message = '')
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
                            <?= Translate::td("Click 'Sent' button to sent the codes") ?>
                        </h5>
                        <?php if ($message) { ?>
                            <label class="blue-text"><?= $message ?></label>
                        <?php } ?>
                        <h5><?= Translate::td('Code from SMS') ?>:</h5>
                        <input type="text" name="code_1" placeholder="<?= Translate::td('Authentication code') ?>">
                        <h5><?= Translate::td('Code from email') ?>:</h5>
                        <?php if (isset($_GET['sent'])) { ?>
                            <input type="text" name="code_2" placeholder="<?= Translate::td('Authentication code') ?>">
                            <div class="row center">
                                <button type="submit" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                    <?= Translate::td('Verify') ?>
                                </button>
                            </div>
                        <?php } ?>
                        <div class="row center">
                            <a href="<?= $url ?>?sent=1" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                <?= isset($_GET['sent']) ? Translate::td('Re-sent') : Translate::td('Sent') ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

}