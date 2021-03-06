<?php

namespace core\views;

use core\models\Coin;
use core\controllers\Coin_controller;
use core\translate\Translate;

class Coin_view
{
    /**
     * @return string
     */
    static public function setRatesForm()
    {
        ob_start();
        ?>
        <div class="row card administrator-settings-block">
            <form class="registration col s12" action="<?= Coin_controller::SETRATES_URL ?>" method="post" autocomplete="off">
                <h5 class="center"><?= Translate::td('Coins rates (deposits) count of usd in one coin') ?></h5>
                <?php foreach (array_merge(Coin::coins(), [Coin::token(), Coin::reinvestToken()]) as $coin) { ?>
                    <label><?= $coin ?>:</label>
                    <input type="number"
                           name="<?= $coin ?>" placeholder="1"
                           value="<?= Coin::getRate($coin) ?>"
                           min="0" max="9999999999" step="0.00000000000001">
                    <br>
                <?php } ?>
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