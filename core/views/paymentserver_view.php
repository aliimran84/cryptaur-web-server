<?php

namespace core\views;

use core\controllers\PaymentServer_controller;
use core\models\PaymentServer;

class PaymentServer_view
{
    /**
     * @param PaymentServer $paymentServer
     * @return string
     */
    static public function setForm($paymentServer)
    {
        ob_start();
        ?>
        <div class="row card administrator-settings-block">
            <form class="registration col s12" action="<?= PaymentServer_controller::SET_URL ?>" method="post">
                <h5 class="center"><?= Translate::td('Payment server') ?></h5>
                <?php if (isset($_GET['err'])) {
                    // todo: decode error
                    ?>
                    <label><?= Translate::td('Error') ?> <?= $_GET['err'] ?></label>
                <?php } ?>
                <label><?= Translate::td('URL') ?>:</label>
                <input type="text" name="url" placeholder="url" value="<?= @$paymentServer->url ?>">
                <label><?= Translate::td('Key id') ?>:</label>
                <input type="text" name="keyid" placeholder="keyid" value="<?= @$paymentServer->keyid ?>">
                <label><?= Translate::td('Secret key') ?>:</label>
                <input type="text" name="secretkey" placeholder="secretkey" value="<?= @$paymentServer->secretkey ?>">
                <div class="row center">
                    <button type="submit" class="waves-effect waves-light btn btn-send" style="width: 100%">
                        <?= Translate::td('Set') ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}