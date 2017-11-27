<?php

namespace core\views;

use core\controllers\Deposit_controller;
use core\engine\Application;
use core\models\Deposit;

class Deposit_view
{
    /**
     * @return string
     */
    static public function setMinimalsForm()
    {
        ob_start();
        ?>
        <div class="row card">
            <form class="registration col s12" action="<?= Deposit_controller::SET_URL ?>" method="post">
                <h5 class="center">Set minimal values</h5>
                Minimal tokens for minting:
                <input type="number"
                       name="<?= Deposit::MINIMAL_TOKENS_FOR_MINTING_KEY ?>" placeholder="1"
                       value="<?= Deposit::minimalTokensForMinting() ?>"
                       min="0" max="9999999" step="0.000000001">
                <br>
                Minimal tokens for bounty:
                <input type="number"
                       name="<?= Deposit::MINIMAL_TOKENS_FOR_BOUNTY_KEY ?>" placeholder="1"
                       value="<?= Deposit::minimalTokensForBounty() ?>"
                       min="0" max="9999999" step="0.000000001">
                <br>
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