<?php

namespace core\views;

use core\engine\Application;
use core\models\Coin;
use core\models\Deposit;
use core\models\Wallet;

class Wallet_view
{
    static private $already_myContribution = false;

    static public function newContribution()
    {
        if (self::$already_myContribution) {
            return '<a href="#my-contribution-section">My Contribution</a>';
        }
        self::$already_myContribution = true;
        ob_start();
        ?>

        <section id="my-contribution-section" class="my-contribution">
            <div class="row">
                <h3>My contribution</h3>
            </div>
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
                            <?php foreach (Coin::COINS as $coin) { ?>
                                <div style="display: none;" id="div-amount-<?= $coin ?>" class="div-amount-coins input-field col s6 m6">
                                    <input class="input-amount" id="input-amount-<?= $coin ?>" type="number"
                                           value="1" min="0" max="9999999" step="0.000000001">
                                    <label for="input-amount-<?= $coin ?>">select amount</label>
                                </div>
                            <?php } ?>
                        </form>
                        <script>
                            (function ($) {
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
                                var investorWalletAddresses = <?= json_encode($wallets, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
                                $(document).ready(function () {
                                    <?php
                                    $coinsRates = [];
                                    foreach (array_merge(Coin::COINS, [Coin::TOKEN]) as $coin) {
                                        $coinsRates[$coin] = Coin::getRate($coin);
                                    }
                                    ?>
                                    var coinsRate = <?= json_encode($coinsRates, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
                                    var minimalTokensForNotToBeDonation = <?= json_encode(Deposit::minimalTokensForNotToBeDonation()) ?>;
                                    var currentCoin = '';
                                    var onAmountChange = function (input) {
                                        var text = $(input).val();
                                        if (text.length === 0) {
                                            return false;
                                        }
                                        var val = parseFloat(text);
                                        if ('' + val !== text) {
                                            $(input).val(val);
                                            return;
                                        } else if (val < 0) {
                                            $(input).val(-val);
                                            return;
                                        }
                                        val = val.toFixed(8);
                                        $('#selected_amount').text(val);
                                        var usd = coinsRate[currentCoin] * val;
                                        var tokens = usd / coinsRate['<?= Coin::TOKEN ?>'];
                                        tokens = tokens.toFixed(8);
                                        $('#calculated_selected_to_cpt').text(tokens);
                                        $('#calculated_cpt_as_donation').toggle(tokens < minimalTokensForNotToBeDonation);
                                    };
                                    $('.input-amount').unbind('keyup change').bind('keyup change', function (e) {
                                        onAmountChange(e.target);
                                    });
                                    $('#select-coins').on('change', function () {
                                        var coin = $(this).val();
                                        currentCoin = coin;
                                        $('.div-amount-coins').hide();
                                        $('#div-amount-' + coin).show();
                                        $('#selected_currency').text(coin);
                                        onAmountChange($('#input-amount-' + coin)[0]);
                                        var walletAddrText = 'Wallet registration in progress';
                                        if (investorWalletAddresses[coin]) {
                                            walletAddrText = investorWalletAddresses[coin];
                                        }
                                        $('#selected_wallet_addr').html(walletAddrText);
                                    }).change();
                                });
                            })(jQuery);
                        </script>
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
                <p>
                    You will get:
                    <span id="calculated_selected_to_cpt"></span>
                    CPT
                    <span id="calculated_cpt_as_donation" style="display: none;">(will be received as donation)</span>
                </p>
            </div>
        </section>

        <?php
        return ob_get_clean();
    }
}