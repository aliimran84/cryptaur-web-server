<?php

namespace core\views;

use core\models\Coin;
use core\controllers\Coin_controller;

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
            <form class="registration col s12" action="<?= Coin_controller::SETRATES_URL ?>" method="post">
                <h5 class="center">Coins rates (deposits)<br>count of usd in one coin</h5>
                <?php foreach (array_merge(Coin::COINS, [Coin::TOKEN]) as $coin) { ?>
                    <label><?= $coin ?>:</label>
                    <input type="number"
                           name="<?= Coin::RATE_KEY_PREFIX . $coin ?>" placeholder="1"
                           value="<?= Coin::getRate($coin) ?>"
                           min="0" max="9999999" step="0.000000001">
                    <br>
                <?php } ?>
                <div class="row center">
                    <button type="submit" class="waves-effect waves-light btn btn-send" style="width: 100%">
                        Set
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}