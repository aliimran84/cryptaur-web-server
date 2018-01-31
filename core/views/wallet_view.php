<?php

namespace core\views;

use core\engine\Application;
use core\models\Coin;
use core\models\Deposit;
use core\models\Wallet;
use core\translate\Translate;

class Wallet_view
{
    static private $calledOnce_newContribution = false;

    static public function newContribution()
    {
        ob_start();

        if (!Deposit::receivingDepositsIsOn() && !@$_SESSION['tester']) {
        ?>
        <section class="my-contribution wallet_view-new_contribution-section">
        <div class="row">
            <h3><?= Translate::td('My contribution') ?></h3>
        </div>
        <div class="row">
            <p><?= Translate::td('Contributions are not possible between stages') ?></p>
        </div>

        <?php
        } else {
            if (!self::$calledOnce_newContribution) {
                self::$calledOnce_newContribution = true;
                ?>
                <script>
                    (function ($) {
                        <?php
                        $addresses = [];
                        $mores = [];
                        foreach (Coin::coins() as $coin) {
                            $wallet = Wallet::getByInvestoridCoin(Application::$authorizedInvestor->id, $coin);
                            $address = null;
                            $more = null;
                            if ($wallet) {
                                $address = @$wallet->address;
                                $more = @$wallet->more;
                            }
                            $addresses[$coin] = $address;
                            $mores[$coin] = $more;
                        }
                        ?>
                        var investorWalletsAddress = <?= json_encode($addresses, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
                        var investorWalletsMore = <?= json_encode($mores, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
                        $(document).ready(function () {
                            <?php
                            $coinsRates = [];
                            foreach (array_merge(Coin::coins(), [Coin::token()]) as $coin) {
                                $coinsRates[$coin] = Coin::getRate($coin);
                            }
                            ?>
                            var coinsRate = <?= json_encode($coinsRates, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
                            var minimalTokensForNotToBeDonation = <?= json_encode(Deposit::minimalTokensForNotToBeDonation()) ?>;
                            var textInputOld = $('.wallet_view-new_contribution-input_amount').val();
                            var onAmountChange = function (input) {
                                var coin = $(input).parent().data('coin');
                                var section = $(input).parents('.wallet_view-new_contribution-section');
                                var text = $(input).val();
                                var val;
                                if (text.length === 0) {
                                    val = parseFloat(textInputOld);
                                    $(input).val(val);
                                    return false;
                                }
                                val = parseFloat(text);
                                if ('' + val !== text) {
                                    $(input).val(val);
                                    textInputOld = val;
                                    return;
                                } else if (val < 0) {
                                    $(input).val(-val);
                                    return;
                                }
                                val = val.toFixed(8);
                                textInputOld = text;
                                section.find('.wallet_view-new_contribution-selected_amount').text(val);

                                var usd = coinsRate[coin] * val;
                                var tokens = (usd / coinsRate['<?= Coin::token() ?>']).toFixed(8);

                                section.find('.wallet_view-new_contribution-calculated_selected_to_tokens').text(tokens);
                                section.find('.wallet_view-new_contribution-calculated_as_donation').toggle(tokens < minimalTokensForNotToBeDonation);
                            };
                            var timeoutChange;
                            $(document).on('keyup change', '.wallet_view-new_contribution-input_amount', function (event) {
                                clearTimeout(timeoutChange);
                                timeoutChange = setTimeout(function(){
                                    onAmountChange(event.target)
                                }, 1000);

                            });
                            $(document).on('click', ['.wallet_view-new_contribution-div_amount .btn-up', '.wallet_view-new_contribution-div_amount .btn-down'], function (event) {
                                onAmountChange($(event.target).closest('.wallet_view-new_contribution-div_amount').find('input')[0]);
                            });
                            $(document).on('change', '.wallet_view-new_contribution-select_coins', function (event) {
                                var coin = $(event.target).val();
                                $('.wallet_view-new_contribution-div_amount').hide();
                                $('.wallet_view-new_contribution-div_amount[data-coin=' + coin + ']').show();
                                var section = $(event.target).parents('.wallet_view-new_contribution-section');
                                var input = section.find('.wallet_view-new_contribution-div_amount[data-coin=' + coin + '] > input');
                                onAmountChange(input);
                                var walletAddrText = 'Wallet registration in progress';
                                var walletMoreText = '';
                                if (investorWalletsAddress[coin]) {
                                    walletAddrText = investorWalletsAddress[coin];
                                }
                                if (investorWalletsMore[coin]) {
                                    walletMoreText = investorWalletsMore[coin];
                                    switch (coin.toLowerCase()) {
                                        case 'xem':
                                            walletMoreText = 'message: ' + walletMoreText;
                                            break;
                                        case 'xrp':
                                            walletMoreText = 'DestinationTag: ' + walletMoreText;
                                            break;
                                    }
                                }
                                section.find('.wallet_view-new_contribution-selected_currency').text(coin);
                                section.find('.wallet_view-new_contribution-selected_wallet_addr').text(walletAddrText);
                                section.find('.wallet_view-new_contribution-selected_wallet_more').text(walletMoreText);
                            });
                        });
                    })(jQuery);
                </script>
                <?php
            }
            ?>

            <section class="my-contribution wallet_view-new_contribution-section">
                <div class="row">
                    <h3><?= Translate::td('My contribution') ?></h3>
                </div>
                <div class="row">
                    <p><?= Translate::td('To learn about the minimum contribution limits') ?> <a target="_blank" href="https://cryptaur.com/stage-2"><?= Translate::td('click here') ?></a></p>
                </div>
                <div class="row">
                    <div class="col s12 offset-m3 m6">
                        <div class="row">
                            <form autocomplete="off">
                                <div class="input-field col s6 m6">
                                    <select class="wallet_view-new_contribution-select_coins">
                                        <?php foreach (Coin::coins() as $coin) { ?>
                                            <option value="<?= $coin ?>"><?= $coin ?></option>
                                        <?php } ?>
                                    </select>
                                    <label><?= Translate::td('select currency') ?></label>
                                </div>
                                <?php foreach (Coin::coins() as $coin) { ?>
                                    <div style="display: none;" data-coin="<?= $coin ?>" class="wallet_view-new_contribution-div_amount input-field col s6 m6">
                                        <input type="number"
                                               class="wallet_view-new_contribution-input_amount"
                                               value="1" min="0" max="9999999999" step="0.00000001">
                                        <label><?= Translate::td('select amount') ?></label>
                                        <span class="btn-up"><i class="material-icons">keyboard_arrow_up</i></span>
                                        <span class="btn-down"><i class="material-icons">keyboard_arrow_down</i></span>
                                    </div>
                                <?php } ?>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <p>
                        <?= Translate::td('Copy address below to send') ?>
                        <span class="wallet_view-new_contribution-selected_amount"></span>
                        <span class="wallet_view-new_contribution-selected_currency"></span>
                    </p>
                    <h5 class="wallet_view-new_contribution-selected_wallet_addr"></h5>
                    <h5 class="wallet_view-new_contribution-selected_wallet_more"></h5>
                    <p>
                        <?= Translate::td('You will get') ?>:
                        <span class="wallet_view-new_contribution-calculated_selected_to_tokens"></span>
                        <?= Coin::token() ?>
                        <span class="wallet_view-new_contribution-calculated_as_donation" style="display: none;">(<?= Translate::td('will be received as donation') ?>)</span>
                    </p>
                    <p>
                        <?= Translate::td('This is an estimate based on the current exchange rate. Your final amount of CPTs will be calculated at the time you actually send your contribution.') ?>
                    </p>
                </div>
            </section>
            <script>
                $(document).ready(function () {
                    $('.wallet_view-new_contribution-select_coins').change();
                });
            </script>

            <?php
        }
        return ob_get_clean();
    }
}