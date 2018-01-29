<?php

namespace core\views;

use core\controllers\Deposit_controller;
use core\engine\Application;
use core\engine\DB;
use core\models\Bounty;
use core\models\Deposit;
use core\models\EtherWallet;
use core\models\EthQueue;
use core\translate\Translate;

class Deposit_view
{
    /**
     * @return string
     */
    static public function setMinimalsForm()
    {
        ob_start();
        ?>
        <div class="row card administrator-settings-block">
            <form class="registration col s12" action="<?= Deposit_controller::TOKENS_SET_URL ?>" method="post" autocomplete="off">
                <h5 class="center"><?= Translate::td('Set minimal values') ?></h5>
                <label><?= Translate::td('Minimal tokens for minting') ?>:</label>
                <input type="number"
                       name="<?= Deposit::MINIMAL_TOKENS_FOR_MINTING_KEY ?>" placeholder="1"
                       value="<?= Deposit::minimalTokensForMinting() ?>"
                       min="0" max="9999999999" step="0.00000001">
                <label><?= Translate::td('Minimal tokens for bounty') ?>:</label>
                <input type="number"
                       name="<?= Deposit::MINIMAL_TOKENS_FOR_BOUNTY_KEY ?>" placeholder="1"
                       value="<?= Deposit::minimalTokensForBounty() ?>"
                       min="0" max="9999999999" step="0.00000001">
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

    /**
     * @return string
     */
    static public function setPersmissionsForm()
    {
        ob_start();
        ?>
        <div class="row card administrator-settings-block">
            <form class="registration col s12" action="<?= Deposit_controller::PERMISSIONS_SET_URL ?>" method="post" autocomplete="off">
                <h5 class="center"><?= Translate::td('Set permissions values') ?></h5>
                <label><?= Translate::td('Receiving deposits is on') ?>:</label>
                <input type="number"
                       name="<?= Deposit::RECEIVING_DEPOSITS_IS_ON ?>" placeholder="1"
                       value="<?= Deposit::receivingDepositsIsOn() ?>"
                       min="0" max="1" step="1">
                <label><?= Translate::td('Minting is on') ?>:</label>
                <input type="number"
                       name="<?= Deposit::MINTING_IS_ON ?>" placeholder="1"
                       value="<?= Deposit::mintingIsOn() ?>"
                       min="0" max="1" step="1">
                <label><?= Translate::td('Withdraw is on') ?>:</label>
                <input type="number"
                       name="<?= Bounty::WITHDRAW_IS_ON ?>" placeholder="1"
                       value="<?= Bounty::withdrawIsOn() ?>"
                       min="0" max="1" step="1">
                <label><?= Translate::td('Reinvest is on') ?>:</label>
                <input type="number"
                       name="<?= Bounty::REINVEST_IS_ON ?>" placeholder="1"
                       value="<?= Bounty::reinvestIsOn() ?>"
                       min="0" max="1" step="1">
                <label>Send eth wallet is on:</label>
                <input type="number"
                       name="<?= EthQueue::SENDETHWALLET_IS_ON ?>" placeholder="1"
                       value="<?= EthQueue::sendEthWalletIsOn() ?>"
                       min="0" max="1" step="1">
                <label>Send cpt wallet is on:</label>
                <input type="number"
                       name="<?= EthQueue::SENDCPTWALLET_IS_ON ?>" placeholder="1"
                       value="<?= EthQueue::sendCptWalletIsOn() ?>"
                       min="0" max="1" step="1">
                <label>Send proof wallet is on:</label>
                <input type="number"
                       name="<?= EthQueue::SENDPROOFWALLET_IS_ON ?>" placeholder="1"
                       value="<?= EthQueue::sendProofWalletIsOn() ?>"
                       min="0" max="1" step="1">
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

    static public function transactions()
    {
        ob_start();
        $deposits = Deposit::investorDeposits(Application::$authorizedInvestor->id);
        if (!$deposits) {
            ?>
            <h3><?= Translate::td('There are no transactions yet') ?></h3>
            <?= Wallet_view::newContribution() ?>
            <?php
        } else {
            ?>
            <div class="head-collapsible">
                <div class="row">
                    <div class="col s1 collapsible-col center"><?= Translate::td('Type') ?></div>
                    <div class="col s4 collapsible-col"><?= Translate::td('Date') ?></div>
                    <div class="col s4 collapsible-col"><?= Translate::td('Description') ?></div>
                    <div class="col s2 collapsible-col"><?= Translate::td('Amount') ?></div>
                    <div class="col s1 collapsible-col"></div>
                </div>
            </div>
            <ul class="collapsible" data-collapsible="accordion">
                <?php
                foreach ($deposits as &$deposit) {
                    ?>
                    <li>
                        <div class="collapsible-header">
                            <div class="row">
                                <div class="col s1 collapsible-col center">
                                    <?= Translate::td('Send') ?>
                                </div>
                                <div class="col s4 collapsible-col">
                                    <?= DB::timetostr($deposit->datetime) ?>
                                </div>
                                <div class="col s4 collapsible-col">
                                    <?php
                                    if ($deposit->is_donation) {
                                        echo Translate::td('Transferred as donation');
                                    } else {
                                        if ($deposit->used_in_minting) {
                                            echo Translate::td('Used in minting');
                                        } else {
                                            echo Translate::td('Not used in minting');
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="col s2 collapsible-col">
                                    <?= $deposit->amount ?> <?= $deposit->coin ?>
                                </div>
                                <div class="col s1 collapsible-col">
                                    <i class="material-icons">keyboard_arrow_right</i>
                                </div>
                            </div>
                        </div>
                        <div class="collapsible-body">
                            <div class="row">
                                <div class="col s1"></div>
                                <div class="col s10">
                                    <p><?= Translate::td('Order Id') ?>:#<?= $deposit->id ?></p>
                                    <p>txid: <?= $deposit->txid ?></p>
                                    <p>vout: <?= $deposit->vout ?></p>
                                </div>
                                <div class="col s1"></div>
                            </div>
                        </div>
                    </li>
                <?php } ?>
            </ul>
        <?php }
        return ob_get_clean();
    }
}