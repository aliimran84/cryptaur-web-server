<?php

namespace core\views;

use core\translate\Translate;
use core\sfchecker\ACTION2FA;
use core\secondfactor\API2FA;

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
                        <?php if (isset($_SESSION[ACTION2FA::TEMP_DATA_SENDED])) { ?>
                            <input
                                type="text"
                                name="otp"
                                placeholder="<?= Translate::td('Authentication code') ?>"
                                required=""
                                pattern="[<?= API2FA::CODE_LETTERS ?>]{<?= API2FA::CODE_LENGTH ?>,<?= API2FA::CODE_LENGTH ?>}"
                            >
                            <div class="row center">
                                <button name="commit" value="1" type="submit" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                    <?= Translate::td('Verify') ?>
                                </button>
                            </div>
                        <?php } ?>
                    </form>
                    <form class="login col s12" action="<?= $url ?>" method="post" autocomplete="off">
                        <div class="row center">
                            <button name="send" value="1" type="submit" class="waves-effect waves-light btn btn-login" style="width: 100%">
                                <?= isset($_SESSION[ACTION2FA::TEMP_DATA_SENDED]) ? Translate::td('Re-send') : Translate::td('Send') ?>
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