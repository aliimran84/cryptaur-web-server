<?php

namespace core\views;

use core\engine\Application;
use core\engine\DB;
use core\models\Deposit;

class Transactions_view
{
    static public function view()
    {
        ob_start() ?>

        <div class="container">
            <?php
            $deposits = Deposit::investorDeposits(Application::$authorizedInvestor->id);
            if (!$deposits) {
                ?>
                <h3>There is no transaction yet</h3>
                <?= Wallet_view::myContribution() ?>
                <?php
            } else foreach ($deposits as $deposit) {
                ?>
                <div class="head-collapsible">
                    <div class="row">
                        <div class="col s1 collapsible-col center">Type</div>
                        <div class="col s2 collapsible-col">Date</div>
                        <div class="col s4 collapsible-col">Description</div>
                        <div class="col s2 collapsible-col center">Status</div>
                        <div class="col s2 collapsible-col">Amount</div>
                        <div class="col s1 collapsible-col"></div>
                    </div>
                </div>
                <ul class="collapsible" data-collapsible="accordion">
                    <li>
                        <div class="collapsible-header">
                            <div class="row">
                                <div class="col s1 collapsible-col center">
                                    Send
                                </div>
                                <div class="col s2 collapsible-col">
                                    <?= DB::timetostr($deposit->datetime) ?>
                                </div>
                                <div class="col s4 collapsible-col">


                                </div>
                                <div class="col s2 collapsible-col center">
                                    <?php
                                    if ($deposit->used_in_bounty) {
                                        echo 'Used in bounty';
                                    } else {
                                        echo 'Not used in bounty';
                                    }
                                    echo '<br>';
                                    if ($deposit->used_in_minting) {
                                        echo 'Used in minting';
                                    } else {
                                        echo 'Not used in minting';
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
                                    <p>Converted to <?= $deposit->usd ?> USD</p>
                                    <p>Order Id:#<?= $deposit->id ?></p>
                                    <p>txid: <?= $deposit->txid ?></p>
                                    <p>vout: <?= $deposit->vout ?></p>
                                </div>
                                <div class="col s1"></div>
                            </div>
                        </div>
                    </li>
                </ul>
            <?php } ?>
        </div>

        <?php
        return ob_get_clean();
    }
}