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
        <div class="row">
            <div class="col s12 m6 offset-m3 l6 offset-l3 xl4 offset-xl4">
                <h3>Payment server</h3>
                <div class="row">
                    <form class="registration col s12" action="<?= PaymentServer_controller::SET_URL ?>" method="post">
                        <?php if (isset($_GET['err'])) {
                            // todo: decode error
                            ?>
                            <label>Error <?= $_GET['err'] ?></label>
                        <?php } ?>
                        <input type="text" name="url" placeholder="url" value="<?= @$paymentServer->url ?>">
                        <input type="text" name="keyid" placeholder="keyid" value="<?= @$paymentServer->keyid ?>">
                        <input type="text" name="secretkey" placeholder="secretkey" value="<?= @$paymentServer->secretkey ?>">
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
}