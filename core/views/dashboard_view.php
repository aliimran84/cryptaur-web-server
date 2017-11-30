<?php

namespace core\views;

use core\engine\Application;
use core\models\Coin;
use core\models\Wallet;

class Dashboard_view
{
    static public function view()
    {
        ob_start();
        ?>

        <div class="row">
            <div class="col s12 m3 left-panel">
                <h3>Token sale</h3>
                <div class="row">
                    <div class="stage active">
                        <h2>Stage 1</h2>
                        <p>Nov 27, 2017</p>
                        <p>Dec 07, 2017</p>
                        <div class="col s12 offset-m1 m10 offset-l2 l8 offset-xl3 xl6">
                            <button class="waves-effect waves-light btn btn-learn-more">Learn more</button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="stage ">
                        <h2>Stage 2</h2>
                        <p>Jan 22, 2018</p>
                        <p>Jan 31, 2018</p>
                        <div class="col s12 offset-m1 m10 offset-l2 l8 offset-xl3 xl6">
                            <button class="waves-effect waves-light btn btn-learn-more">Learn more</button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="stage ">
                        <h2>Stage 3</h2>
                        <p>Feb 12, 2018</p>
                        <p>Feb 20, 2018</p>
                        <div class="col s12 offset-m1 m10 offset-l2 l8 offset-xl3 xl6">
                            <button class="waves-effect waves-light btn btn-learn-more">Learn more</button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="stage ">
                        <h2>Stage 4</h2>
                        <p>Mar 05, 2018</p>
                        <p>Mar 12, 2018</p>
                        <div class="col s12 offset-m1 m10 offset-l2 l8 offset-xl3 xl6">
                            <button class="waves-effect waves-light btn btn-learn-more">Learn more</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col s12 m9 main-panel">
                <div class="row indicators">
                    <div class="col s12 m4">
                        <h4>Total tokens issued</h4>
                        <h3>CPT 3,532,715,580</h3>
                    </div>
                    <div class="col s12 m4">
                        <h4>Total funds raised</h4>
                        <h3>US$ 28,567,845</h3>
                    </div>
                    <div class="col s12 m4">
                        <h4>Total participants</h4>
                        <h3>45,836</h3>
                    </div>
                </div>
                <section class="my-tokens">
                    <div class="row">
                        <h3>My tokens</h3>
                    </div>
                    <div class="row">
                        <div class="col s12 m6 main-panel-block">
                            <?= self::myContributionsBlock() ?>
                        </div>
                        <div class="col s12 m6 main-panel-block">
                            <h3>Bounty</h3>
                            <div class="amount-wallet">
                                0 ETH
                            </div>
                            <div class="amount input-field">
                                <input type="number" name="amount" value="0" min="0" max="0" step=".0000001">
                                <label>select amount</label>
                            </div>
                            <div class="amount input-field">
                                <button class="waves-effect waves-light btn ">Withdraw</button>
                            </div>
                            <div class="amount input-field">
                                <button class="waves-effect waves-light btn ">Reinvest</button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
        <div class="row">
            <div class="col s12 m3 left-panel">
                <h3>Current stage</h3>
                <div class="current-stage">
                    <h2>Stage 1</h2>
                    <p>Nov 27, 2017</p>
                    <p>Dec 07, 2017</p>
                    <div class="shares">
                        <p>1st day</p>
                        <h2>40% Discount</h2>
                        <p>Invite your friends and earn bounties during the Cryptaur token sale</p>
                    </div>
                    <div class="shares">
                        <p>All other days</p>
                        <h2>20% Discount</h2>
                        <p>Invite your friends and earn bounties during the Cryptaur token sale</p>
                    </div>
                </div>
            </div>
            <div class="col s12 m9 main-panel">
                <?= Wallet_view::newContribution() ?>
            </div>
        </div>
        <div class="row">
            <div class="col s12 m3 left-panel">
                <h3>Referral program</h3>
                <div class="referral-progam">
                    <div class="line-left"></div>
                    <div class="line-bottom"></div>
                    <div class="circle"></div>
                    <div class="circle"></div>
                    <div class="circle"></div>
                    <div class="circle"></div>
                    <div class="circle"></div>
                    <div class="circle"></div>
                    <ul>
                        <li><h2>1st Level: 3%</h2></li>
                        <li><h2>2st Level: 3%</h2></li>
                        <li><h2>3st Level: 3%</h2></li>
                        <li><h2>4st Level: 3%</h2></li>
                        <li><h2>5st Level: 4%</h2></li>
                        <li><h2>6st Level: 4%</h2></li>
                    </ul>
                </div>
            </div>
            <?= self::myGroup() ?>
        </div>

        <?php
        return ob_get_clean();
    }

    static public function myContributionsBlock()
    {
        ob_start();
        ?>
        <h3>Purchased</h3>
        <div class="amount-wallet">
            0 CPT
        </div>
        <div class="amount input-field">
            <h5>Contributed</h5>
            <ul>
                <?php
                $totalUsd = 0;
                foreach (Coin::COINS as $coin) {
                    $wallet = Wallet::getByInvestoridCoin(Application::$authorizedInvestor->id, $coin);
                    $balance = 0;
                    if ($wallet) {
                        $balance = $wallet->balance;
                        $totalUsd += $wallet->usdUsed;
                    }
                    ?>
                    <li><span><?= $coin ?></span><span><?= $balance ?></span></li>
                <?php } ?>
                <li><h5>Total in USD</h5><h5>$<?= $totalUsd ?></h5></li>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    static public function myGroup()
    {
        ob_start();
        ?>

        <div class="col s12 m9 main-panel">
            <section class="my-group">
                <div class="row">
                    <h3>My group</h3>
                </div>
                <div class="row">
                    <div class="col s12 l4 main-panel-block">
                        <h3>Raised by group: US$ 123,567.89</h3>
                    </div>
                    <div class="col s12 l4 main-panel-block">
                        <h3>Group participants: 247</h3>
                    </div>
                    <div class="col s12 l4 main-panel-block">
                        <div class="input-field">
                            <button class="waves-effect waves-light btn ">Invite friends</button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="main-panel-block tree">
                        <ul class="first-level">
                            <li class="first-level">
                                <div class="tree-block">
                                    <h2>Sponsor name</h2>
                                    <p>Contributed</p>
                                    <h3>US$ 12399.546</h3>
                                </div>
                                <div class="line-bottom"></div>
                            </li>
                            <li>
                                <ul class="second-level">
                                    <li class="second-level participants">
                                        <div class="tree-block">
                                            <h2>My name</h2>
                                            <p>Contributed</p>
                                            <h3>US$ 12399.546</h3>
                                        </div>
                                        <div class="line-right"></div>
                                        <div class="participants-block">
                                            <h2>2 participants in level<i class="material-icons">expand_more</i></h2>
                                        </div>
                                        <div class="line-left"></div>
                                        <div class="line-bottom" style="display:none;"></div>
                                    </li>
                                    <li>
                                        <ul class="third-level participants close">
                                            <li class="third-level">
                                                <div class="tree-block">
                                                    <h2>My name</h2>
                                                    <p>Contributed</p>
                                                    <h3>US$ 12399.546</h3>
                                                </div>
                                                <div class="line-left"></div>
                                            </li>
                                            <li class="third-level">
                                                <div class="tree-block">
                                                    <h2>My name</h2>
                                                    <p>Contributed</p>
                                                    <h3>US$ 12399.546</h3>
                                                </div>
                                                <div class="line-left"></div>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>

                </div>
            </section>
        </div>

        <?php
        return ob_get_clean();
    }

}