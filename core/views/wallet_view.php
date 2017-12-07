<?php

namespace core\views;

use core\engine\Application;
use core\models\Coin;
use core\models\Deposit;
use core\models\Wallet;

class Wallet_view
{
    static private $calledOnce_newContribution = false;

    static public function newContribution()
    {
        ob_start();

        if (!self::$calledOnce_newContribution) {
            self::$calledOnce_newContribution = true;
            ?>
            <script>
                (function ($) {
                    <?php
                    $wallets = [];
                    foreach (Coin::coins() as $coin) {
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
                        foreach (array_merge(Coin::coins(), [Coin::token()]) as $coin) {
                            $coinsRates[$coin] = Coin::getRate($coin);
                        }
                        ?>
                        var coinsRate = <?= json_encode($coinsRates, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
                        var minimalTokensForNotToBeDonation = <?= json_encode(Deposit::minimalTokensForNotToBeDonation()) ?>;
                        var onAmountChange = function (input) {
                            var coin = $(input).parent().data('coin');
                            var section = $(input).parents('.wallet_view-new_contribution-section');
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
                            section.find('.wallet_view-new_contribution-selected_amount').text(val);

                            var usd = coinsRate[coin] * val;
                            var tokens = (usd / coinsRate['<?= Coin::token() ?>']).toFixed(8);

                            section.find('.wallet_view-new_contribution-calculated_selected_to_tokens').text(tokens);
                            section.find('.wallet_view-new_contribution-calculated_as_donation').toggle(tokens < minimalTokensForNotToBeDonation);
                        };
                        $(document).on('keyup change', '.wallet_view-new_contribution-input_amount', function (event) {
                            onAmountChange(event.target);
                        });
                        $(document).on('change', '.wallet_view-new_contribution-select_coins', function (event) {
                            var coin = $(event.target).val();
                            $('.wallet_view-new_contribution-div_amount').hide();
                            $('.wallet_view-new_contribution-div_amount[data-coin=' + coin + ']').show();
                            var section = $(event.target).parents('.wallet_view-new_contribution-section');
                            var input = section.find('.wallet_view-new_contribution-div_amount[data-coin=' + coin + '] > input');
                            onAmountChange(input);
                            var walletAddrText = 'Wallet registration in progress';
                            if (investorWalletAddresses[coin]) {
                                walletAddrText = investorWalletAddresses[coin];
                            }
                            section.find('.wallet_view-new_contribution-selected_currency').text(coin);
                            section.find('.wallet_view-new_contribution-selected_wallet_addr').text(walletAddrText);
                        });
                    });
                })(jQuery);
            </script>
            <?php
        }
        ?>

        <section class="my-contribution wallet_view-new_contribution-section">
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
                                <select class="wallet_view-new_contribution-select_coins">
                                    <?php foreach (Coin::coins() as $coin) { ?>
                                        <option value="<?= $coin ?>"><?= $coin ?></option>
                                    <?php } ?>
                                </select>
                                <label>select currency</label>
                            </div>
                            <?php foreach (Coin::coins() as $coin) { ?>
                                <div style="display: none;" data-coin="<?= $coin ?>" class="wallet_view-new_contribution-div_amount input-field col s6 m6">
                                    <input type="number"
                                           class="wallet_view-new_contribution-input_amount"
                                           value="1" min="0" max="9999999999" step="0.00000001">
                                    <label>select amount</label>
                                </div>
                            <?php } ?>
                        </form>
                    </div>
                </div>
            </div>
            <div class="row">
                <p>
                    Copy address below to send
                    <span class="wallet_view-new_contribution-selected_amount"></span>
                    <span class="wallet_view-new_contribution-selected_currency"></span>
                </p>
                <h5 class="wallet_view-new_contribution-selected_wallet_addr"></h5>
                <p>
                    You will get:
                    <span class="wallet_view-new_contribution-calculated_selected_to_tokens"></span>
                    <?= Coin::token() ?>
                    <span class="wallet_view-new_contribution-calculated_as_donation" style="display: none;">(will be received as donation)</span>
                </p>
            </div>
        </section>
        <script>
            $(document).ready(function () {
                $('.wallet_view-new_contribution-select_coins').change();
            });
        </script>

        <?php
        return ob_get_clean();
    }
}