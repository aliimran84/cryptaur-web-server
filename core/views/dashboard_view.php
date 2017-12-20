<?php

namespace core\views;

use core\controllers\Bounty_controller;
use core\engine\Application;
use core\models\Bounty;
use core\models\Coin;
use core\models\Investor;
use core\models\Wallet;
use core\translate\Translate;

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
                        <h2><?= Translate::td('Stage') ?> 1</h2>
                        <p>Nov 27, 2017</p>
                        <p>Dec 07, 2017</p>
                        <!--                        <div class="col s12 offset-m1 m10 offset-l2 l8 offset-xl3 xl6">-->
                        <!--                            <button class="waves-effect waves-light btn btn-learn-more">Learn more</button>-->
                        <!--                        </div>-->
                    </div>
                </div>
                <div class="row">
                    <div class="stage ">
                        <h2><?= Translate::td('Stage') ?> 2</h2>
                        <p>Jan 22, 2018</p>
                        <p>Jan 31, 2018</p>
                        <!--                        <div class="col s12 offset-m1 m10 offset-l2 l8 offset-xl3 xl6">-->
                        <!--                            <button class="waves-effect waves-light btn btn-learn-more">Learn more</button>-->
                        <!--                        </div>-->
                    </div>
                </div>
                <div class="row">
                    <div class="stage ">
                        <h2><?= Translate::td('Stage') ?> 3</h2>
                        <p>Feb 12, 2018</p>
                        <p>Feb 20, 2018</p>
                        <!--                        <div class="col s12 offset-m1 m10 offset-l2 l8 offset-xl3 xl6">-->
                        <!--                            <button class="waves-effect waves-light btn btn-learn-more">Learn more</button>-->
                        <!--                        </div>-->
                    </div>
                </div>
                <div class="row">
                    <div class="stage ">
                        <h2><?= Translate::td('Stage') ?> 4</h2>
                        <p>Mar 05, 2018</p>
                        <p>Mar 12, 2018</p>
                        <!--                        <div class="col s12 offset-m1 m10 offset-l2 l8 offset-xl3 xl6">-->
                        <!--                            <button class="waves-effect waves-light btn btn-learn-more">Learn more</button>-->
                        <!--                        </div>-->
                    </div>
                </div>
            </div>
            <div class="col s12 m9 main-panel">
                <div class="row indicators">
                    <div class="col s12 m4">
                        <h4><?= Translate::td('Total tokens issued') ?></h4>
                        <h3><?= Coin::token() ?> <?= Investor::totalTokens() ?></h3>
                    </div>
                    <div class="col s12 m4">
                        <h4><?= Translate::td('Total funds raised') ?></h4>
                        <h3>US$ <?= (int)Wallet::totalUsdUsed() ?></h3>
                    </div>
                    <div class="col s12 m4">
                        <h4><?= Translate::td('Total participants') ?></h4>
                        <h3><?= Investor::totalInvestors() ?></h3>
                    </div>
                </div>
                <section class="my-tokens">
                    <div class="row">
                        <h3><?= Translate::td('My tokens') ?></h3>
                    </div>
                    <div class="row">
                        <div class="col s12 m6 main-panel-block">
                            <?= self::myContributionsBlock() ?>
                        </div>
                        <div class="col s12 m6 main-panel-block">
                            <h3><?= Translate::td('Bounty') ?></h3>
                            <div class="amount-wallet">
                                <?= Application::$authorizedInvestor->eth_bounty ?> <?= Coin::COMMON_COIN ?>
                            </div>
                            <form action="<?= Bounty_controller::INVESTOR_REALIZE_URL ?>" method="post">
                                <div class="amount input-field">
                                    <input type="number" name="amount" value="0" min="0" max="<?= Application::$authorizedInvestor->eth_bounty ?>" step="0.00000001">
                                    <label><?= Translate::td('select amount') ?></label>
                                </div>
                                <div class="amount input-field">
                                    <button type="submit" name="action" value="withdraw" class="waves-effect waves-light btn "
                                        <?= (Bounty::withdrawIsOn() ? '' : 'disabled') ?>>
                                        <?= Translate::td('Withdraw') ?>
                                    </button>
                                </div>
                                <div class="amount input-field">
                                    <button type="submit" name="action" value="reinvest" class="waves-effect waves-light btn "
                                        <?= (Bounty::reinvestIsOn() ? '' : 'disabled') ?>>
                                        <?= Translate::td('Reinvest') ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>
            </div>
        </div>
        <div class="row">
            <div class="col s12 m3 left-panel">
                <?php /*
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
                */ ?>
            </div>
            <div class="col s12 m9 main-panel">
                <?= Wallet_view::newContribution() ?>
            </div>
        </div>
        <div class="row">
            <div class="col s12 m3 left-panel">
                <h3><?= Translate::td('Referral program') ?></h3>
                <div class="referral-progam">
                    <div class="line-left"></div>
                    <div class="line-bottom"></div>
                    <?php foreach (Bounty::CURRENT_BOUNTY_PROGRAM as $value) { ?>
                        <div class="circle"></div>
                    <?php } ?>
                    <ul>
                        <?php
                        $rewardByLevel = [];
                        Bounty::rewardForInvestor(Application::$authorizedInvestor, $rewardByLevel);
                        ?>
                        <?php foreach (Bounty::CURRENT_BOUNTY_PROGRAM as $i => $value) { ?>
                            <li>
                                <h2>
                                    <?= $i + 1 ?> <?= Translate::td('Level') ?>: <?= $value ?>%,<br>
                                    US$ <?= number_format(@$rewardByLevel[$i + 1], 2) ?>
                                </h2>
                            </li>
                        <?php } ?>
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
        <h3><?= Translate::td('Purchased') ?></h3>
        <div class="amount-wallet">
            <?= Application::$authorizedInvestor->tokens_count ?> <?= Coin::token() ?>
        </div>
        <div class="amount input-field">
            <h5><?= Translate::td('Contributed') ?></h5>
            <ul>
                <?php
                foreach (Coin::coins() as $coin) {
                    $wallet = Wallet::getByInvestoridCoin(Application::$authorizedInvestor->id, $coin);
                    $balance = 0;
                    if ($wallet) {
                        $balance = $wallet->balance;
                    }
                    ?>
                    <li><span><?= $coin ?></span><span><?= $balance ?></span></li>
                <?php } ?>
                <li><h5><?= Translate::td('Total in USD') ?></h5><h5>
                        $<?= Application::$authorizedInvestor->usdUsed() ?></h5></li>
                <li><h5><?= Translate::td('Withdrawn') ?></h5><h5><?= Application::$authorizedInvestor->eth_withdrawn ?>
                        ETH</h5></li>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * @param Investor $investor
     * @return string
     */
    static private function investorCard(&$investor)
    {
        ob_start();
        ?>
        <div class="tree-block">
            <h2><?= $investor->email ?></h2>
            <p><?= Translate::td('Contributed') ?></p>
            <h3>US$ <?= $investor->usdUsed() ?></h3>
        </div>
        <?php
        return ob_get_clean();
    }

    static public function myGroup()
    {
        $referrer = Investor::getById(Application::$authorizedInvestor->referrer_id);
        Application::$authorizedInvestor->initReferalls(count(Bounty::program()));
        Application::$authorizedInvestor->initCompressedReferalls(count(Bounty::program()));
        ob_start();
        ?>

        <div class="col s12 m9 main-panel">
            <section class="my-group">
                <div class="row">
                    <h3><?= Translate::td('My group') ?></h3>
                </div>
                <div class="row">
                    <div class="col s12 l4 main-panel-block">
                        <h3><?= Translate::td('Raised by group') ?>:
                            US$ <?= Bounty::rewardForInvestor(Application::$authorizedInvestor) ?></h3>
                    </div>
                    <div class="col s12 l4 main-panel-block">
                        <h3>
                            <?= Translate::td('Participants') ?>:
                            <?= Application::$authorizedInvestor->referralsCount() ?>
                        </h3>
                    </div>
                    <div class="col s12 l4 main-panel-block">
                        <div class="input-field">
                            <button class="waves-effect waves-light btn "><?= Translate::td('Invite friends') ?></button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <p class="after-compression">
                        <input type="checkbox" id="after-compression"/>
                        <label for="after-compression"><?= Translate::td('After compression') ?></label>
                    </p>
                    <div class="main-panel-block tree before-compression active">
                        <ul class="first-level">
                            <?php if ($referrer) { ?>
                                <li class="first-level">
                                    <?= self::investorCard($referrer) ?>
                                    <div class="line-bottom"></div>
                                </li>
                            <?php } ?>
                            <li>
                                <ul class="second-level">
                                    <li class="second-level participants">
                                        <?= self::investorCard(Application::$authorizedInvestor) ?>
                                        <div class="line-right"></div>
                                        <div class="participants-block">
                                            <h2><?= count(Application::$authorizedInvestor->referrals) ?> <?= Translate::td('participants in level') ?>
                                                <i class="material-icons">expand_more</i></h2>
                                        </div>
                                        <?php if ($referrer) { ?>
                                            <div class="line-left"></div>
                                        <?php } ?>
                                        <div class="line-bottom" style="display:none;"></div>
                                    </li>
                                    <li>
                                        <?= self::groupSubTree_beforeCompress(Application::$authorizedInvestor) ?>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                    <div class="main-panel-block tree after-compression">
                        <ul class="first-level">
                            <?php if ($referrer) { ?>
                                <li class="first-level">
                                    <?= self::investorCard($referrer) ?>
                                    <div class="line-bottom"></div>
                                </li>
                            <?php } ?>
                            <li>
                                <ul class="second-level">
                                    <li class="second-level participants">
                                        <?= self::investorCard(Application::$authorizedInvestor) ?>
                                        <div class="line-right"></div>
                                        <div class="participants-block">
                                            <h2><?= count(Application::$authorizedInvestor->compressed_referrals) ?>
                                                <?= Translate::td('participants in level') ?><i class="material-icons">expand_more</i>
                                            </h2>
                                        </div>
                                        <?php if ($referrer) { ?>
                                            <div class="line-left"></div>
                                        <?php } ?>
                                        <div class="line-bottom" style="display:none;"></div>
                                    </li>
                                    <li>
                                        <?= self::groupSubTree_afterCompress(Application::$authorizedInvestor) ?>
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

    /**
     * @param Investor $investor
     * @return string html
     */
    static private function groupSubTree_beforeCompress(&$investor)
    {
        ob_start();
        ?>

        <ul class="third-level participants close">
            <?php foreach ($investor->referrals as $referral) { ?>
                <li class="third-level participants">
                    <?= self::investorCard($referral) ?>
                    <?php if ($referral->referrals) { ?>
                        <div class="line-right"></div>
                        <div class="participants-block">
                            <h2><?= count($referral->referrals) ?> <?= Translate::td('participants in level') ?>
                                <i class="material-icons">expand_more</i></h2>
                        </div>
                    <?php } ?>
                    <div class="line-left"></div>
                    <?php if ($referral->referrals) { ?>
                        <div class="line-bottom" style="display:none;"></div>
                    <?php } ?>
                </li>
                <?php if ($referral->referrals) { ?>
                    <li>
                        <?= self::groupSubTree_beforeCompress($referral) ?>
                    </li>
                <?php } ?>
            <?php } ?>
        </ul>

        <?php
        return ob_get_clean();
    }

    /**
     * @param Investor $investor
     * @return string html
     */
    static private function groupSubTree_afterCompress(&$investor)
    {
        ob_start();
        ?>

        <ul class="third-level participants close">
            <?php foreach ($investor->compressed_referrals as &$referral) { ?>
                <li class="third-level participants">
                    <?= self::investorCard($referral) ?>
                    <?php if ($referral->compressed_referrals) { ?>
                        <div class="line-right"></div>
                        <div class="participants-block">
                            <h2><?= count($referral->compressed_referrals) ?> <?= Translate::td('participants in level') ?>
                                <i class="material-icons">expand_more</i></h2>
                        </div>
                    <?php } ?>
                    <div class="line-left"></div>
                    <?php if ($referral->compressed_referrals) { ?>
                        <div class="line-bottom" style="display:none;"></div>
                    <?php } ?>
                </li>
                <?php if ($referral->compressed_referrals) { ?>
                    <li>
                        <?= self::groupSubTree_afterCompress($referral) ?>
                    </li>
                <?php } ?>
            <?php } ?>
        </ul>

        <?php
        return ob_get_clean();
    }
}