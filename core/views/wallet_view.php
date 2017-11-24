<?php

namespace core\views;

use core\engine\Application;
use core\models\Coin;
use core\models\Wallet;

class Wallet_view
{
    static public function myContribution()
    {
        ob_start();
        ?>

        <section class="my-contribution">
            <div class="row">
                <h3>My contribution</h3>
            </div>
            <?php
            //<div class="row">
            //<p>
            //<input type="checkbox" id="debit-card" checked="checked"/>
            //<label for="debit-card">I prefer to purchase CPT tokens using my credit or debit card</label>
            //<img src="images/visa_mastercard.png">
            //</p>
            //</div>
            ?>
            <div class="row">
                <p>To learn about the minimum contribution limits <a href="./">click here</a></p>
            </div>
            <div class="row">
                <div class="col s12 offset-m3 m6">
                    <div class="row">
                        <form>
                            <div class="input-field col s6 m6">
                                <select id="select-coins">
                                    <?php foreach (Coin::COINS as $coin) { ?>
                                        <option value="<?= $coin ?>"><?= $coin ?></option>
                                    <?php } ?>
                                </select>
                                <label for="select-coins">select currency</label>
                            </div>
                            <script>
                                <?php
                                $wallets = [];
                                foreach (Coin::COINS as $coin) {
                                    $wallet = Wallet::getByInvestoridCoin(Application::$authorizedInvestor->id, $coin);
                                    $address = null;
                                    if ($wallet) {
                                        $address = @$wallet->address;
                                    }
                                    $wallets[$coin] = $address;
                                }
                                ?>
                                window.investorWallets = <?= json_encode($wallets, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
                            </script>
                            <?php foreach (Coin::COINS as $coin) { ?>
                                <div style="display: none;" id="div-amount-<?= $coin ?>" class="div-amount-coins input-field col s6 m6">
                                    <input id="input-amount-<?= $coin ?>" type="number"
                                           onchange="window.onAmountChange(this)" onkeyup="window.onAmountChange(this)"
                                           value="1" min="0" max="9999999" step="0.000000001">
                                    <label for="input-amount-<?= $coin ?>">select amount</label>
                                </div>
                            <?php } ?>
                            <?php
                            //<div class="input-field col s12 m4">
                            //<button class="waves-effect waves-light btn btn-contribute">Contribute</button>
                            //</div>
                            ?>
                        </form>
                    </div>
                </div>
            </div>
            <div class="row">
                <p>
                    Copy address below to send
                    <span id="selected_amount"></span>
                    <span id="selected_currency"></span>
                </p>
                <h5 id="selected_wallet_addr"></h5>
                <p>You will get: <span id="calculated_selected_to_cpt"></span> CPT</p>
                <?php
                //<p>Time left: 23 min</p>
                ?>
                <?php
                //<div class="input-field col s12 center">
                //<button class="waves-effect waves-light btn btn-send">Send</button>
                //</div>
                ?>
            </div>
        </section>

        <?php
        return ob_get_clean();
    }
}