<?php

namespace core\views;

use core\controllers\Bounty_controller;
use core\controllers\Investor_controller;
use core\engine\Application;
use core\models\Bounty;
use core\models\Coin;
use core\models\Investor;
use core\models\Wallet;
use core\translate\Translate;

class Dashboard_view
{
    const BOUNTY_ERR = 'bounty_err';

    static public function view()
    {
        ob_start();
        ?>

        <div class="row">
            <div class="col s12 m3 left-panel">
                <h3><?= Translate::td('Token sale') ?></h3>
                <div class="row">
                    <div class="stage">
                        <h2><?= Translate::td('Stage') ?> 1</h2>
                        <p><?= Translate::td('Nov') ?> 27, 2017</p>
                        <p><?= Translate::td('Dec') ?> 07, 2017</p>
                        <!--                        <div class="col s12 offset-m1 m10 offset-l2 l8 offset-xl3 xl6">-->
                        <!--                            <button class="waves-effect waves-light btn btn-learn-more">Learn more</button>-->
                        <!--                        </div>-->
                    </div>
                </div>
                <div class="row">
                    <div class="stage active">
                        <h2><?= Translate::td('Stage') ?> 2</h2>
                        <p><?= Translate::td('Jan') ?> 22, 2018</p>
                        <p><?= Translate::td('Jan') ?> 31, 2018</p>
                        <!--                        <div class="col s12 offset-m1 m10 offset-l2 l8 offset-xl3 xl6">-->
                        <!--                            <button class="waves-effect waves-light btn btn-learn-more">Learn more</button>-->
                        <!--                        </div>-->
                    </div>
                </div>
                <div class="row">
                    <div class="stage ">
                        <h2><?= Translate::td('Stage') ?> 3</h2>
                        <p><?= Translate::td('Feb') ?> 12, 2018</p>
                        <p><?= Translate::td('Feb') ?> 20, 2018</p>
                        <!--                        <div class="col s12 offset-m1 m10 offset-l2 l8 offset-xl3 xl6">-->
                        <!--                            <button class="waves-effect waves-light btn btn-learn-more">Learn more</button>-->
                        <!--                        </div>-->
                    </div>
                </div>
                <div class="row">
                    <div class="stage ">
                        <h2><?= Translate::td('Stage') ?> 4</h2>
                        <p><?= Translate::td('Mar') ?> 05, 2018</p>
                        <p><?= Translate::td('Mar') ?> 12, 2018</p>
                        <!--                        <div class="col s12 offset-m1 m10 offset-l2 l8 offset-xl3 xl6">-->
                        <!--                            <button class="waves-effect waves-light btn btn-learn-more">Learn more</button>-->
                        <!--                        </div>-->
                    </div>
                </div>
                <div class="row">
                    <div style="text-align: center;">
                        <img src="/images/cpt-coin-260.gif" style="width: 90%; max-width: 235px;" alt="cpt-coin">
                    </div>
                </div>
            </div>
            <div class="col s12 m9 main-panel">
                <div class="row tokens-info">
                    <h3><?= Translate::td('TO SEE YOUR TOKENS IN YOUR OWN WALLET ENTER THE FOLLOWING VALUES') ?></h3>
                    <div class="col s12 m12 l6 xl4">
                        <h4><?= Translate::td('Token Contract Address') ?></h4>
                        <h5><?= ETH_TOKENS_CONTRACT ?></h5>
                    </div>
                    <div class="col s12 m6 l3 xl4">
                        <h4><?= Translate::td('Token Symbol') ?></h4>
                        <h5><?= Coin::token() ?></h5>
                    </div>
                    <div class="col s12 m6 l3 xl4">
                        <h4><?= Translate::td('Decimal') ?></h4>
                        <h5>8</h5>
                    </div>
                </div>
                <div class="row indicators">
                    <div class="col s12 m3">
                        <h4><?= Translate::td('Total tokens minted') ?></h4>
                        <h3><?= Coin::token() ?> <?= Investor::totalTokens() ?></h3>
                    </div>
                    <div class="col s12 m3">
                        <h4><?= Translate::td('Total participants') ?></h4>
                        <h3><?= Investor::totalInvestors() ?></h3>
                    </div>
                    <div class="col s12 m3">
                        <h4><?= Translate::td('Total coin contributed', ['coin' => 'BTC']) ?></h4>
                        <h3>BTC <?= (int)Wallet::totalCoinsUsed('btc') ?></h3>
                    </div>
                    <div class="col s12 m3">
                        <h4><?= Translate::td('Total coin contributed', ['coin' => 'ETH']) ?></h4>
                        <h3>ETH <?= (int)Wallet::totalCoinsUsed('eth') ?></h3>
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
                                <?= number_format(Application::$authorizedInvestor->eth_bounty, 8, '.', '') ?> <?= Coin::COMMON_COIN ?>
                            </div>
                            <form class="reinvest-form" action="<?= Bounty_controller::INVESTOR_REALIZE_URL ?>" method="post">
                                <div class="amount input-field">
                                    <?php
                                    if (@$_GET[self::BOUNTY_ERR]) {
                                        ?>
                                        <p class="range-field__label title red-text">
                                            <?= Translate::td('Bounty was not withdrawn or reinvested. Maybe the service is overloaded. Try in a few minutes.') ?>
                                            (<?= $_GET[self::BOUNTY_ERR] ?>)
                                        </p>
                                        <?php
                                    }
                                    ?>
                                    <p class="range-field__label title"><?= Translate::td('Split bounty between reinvest and withdraw') ?></p>
                                    <p><?= Translate::td('Part to reinvest') ?>:</p>
                                    <input type="text" disabled class="reinvest" value="<?= number_format(Application::$authorizedInvestor->eth_bounty, 8, '.', '') ?>">
                                    <br>
                                    <p><?= Translate::td('Part to withdraw') ?>:</p>
                                    <input type="text" disabled class="withdraw" value="0.00000000">
                                    <input type="text" class="percents" name="percentsForReinvesting" value="100">
                                </div>
                                <div class="amount input-field">
                                    <p class="range-field">
                                        <input type="range" min="0" max="100" value="100"/>
                                    </p>
                                    <p class="range-field__label"><?= Translate::td('Drag slider to adjust values') ?></p>
                                </div>
                                <div class="amount input-field">
                                    <button type="submit" class="waves-effect waves-light btn "
                                        <?= (Bounty::withdrawIsOn() && Bounty::reinvestIsOn() ? '' : 'disabled') ?>>
                                        <?= Translate::td('Reinvest') ?>,&nbsp;<?= Translate::td('Withdraw') ?>
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
                <h3><?= Translate::td('Referral Program') ?></h3>
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
                                    <?php /*
                                    <?= Coin::COMMON_COIN ?> <?= number_format(@$rewardByLevel[$i + 1], 2, '.', '') ?>
                                    */ ?>
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
                    <li><span><?= $coin ?></span><span><?= number_format($balance, 4, '.', '') ?></span></li>
                <?php } ?>
                <li>
                    <h5>
                        <?= Translate::td('Withdrawn') ?>
                    </h5><h5>
                        <?= number_format(Application::$authorizedInvestor->eth_withdrawn, 4, '.', '') ?>
                        ETH
                    </h5>
                </li>
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
            <h2><?= $investor->firstname ?> <?= $investor->lastname ?></h2>
            <h3><?= Coin::token() ?> <?= $investor->tokens_count ?></h3>
            <?php /* // todo: временно убираю, т.к. на больших деревьях ну очень долго отрабатывается
            <p>
                <?= Translate::td('Contributed') ?>
                <?php
                foreach ($investor->coinsUsed() as $coin => $balance) {
                    $coin = strtoupper($coin);
                    echo "<br>$coin $balance";
                }
                ?>
            </p>
            */ ?>
        </div>
        <?php
        return ob_get_clean();
    }

    static public function myGroup()
    {
        $referrer = Investor::getById(Application::$authorizedInvestor->referrer_id);
        Application::$authorizedInvestor->initReferrals(count(Bounty::program()));
        Application::$authorizedInvestor->initCompressedReferrals(count(Bounty::program()));
        ob_start();
        ?>

        <div class="col s12 m9 main-panel">
            <section class="my-group">
                <div class="row">
                    <h3><?= Translate::td('My group') ?></h3>
                </div>
                <div class="row">
                    <div class="col s12 l4 main-panel-block">
                        <?php /*
                        <h3>BTC: <?= (int)Wallet::totalCoinsUsed('btc') ?> / ETH: <?= (int)Wallet::totalCoinsUsed('eth') ?></h3>
                        */ ?>
                        <h3>ETH REWARD: <?= Bounty::rewardForInvestor(Application::$authorizedInvestor) ?></h3>
                    </div>
                    <div class="col s12 l4 main-panel-block">
                        <h3>
                            <?= Translate::td('Participants') ?>:
                            <?= Application::$authorizedInvestor->referralsCount() ?>
                        </h3>
                    </div>
                    <div class="col s12 l4 main-panel-block">
                        <div class="input-field">
                            <a href="<?= Investor_controller::INVITE_FRIENDS_URL ?>" class="waves-effect waves-light btn"><?= Translate::td('Invite friends') ?></a>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <!--                    <p class="after-compression">-->
                    <!--                        <input type="checkbox" id="after-compression"/>-->
                    <!--                        <label for="after-compression">-->
                    <?//= Translate::td('After compression')
                    ?><!--</label>-->
                    <!--                    </p>-->
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
                                            <h2>
                                                <?= Translate::td('Participants in level') ?>:
                                                <?= count(Application::$authorizedInvestor->referrals) ?>
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
                                            <h2>
                                                <?= Translate::td('Participants in level') ?>:
                                                <?= count(Application::$authorizedInvestor->compressed_referrals) ?>
                                                <i class="material-icons">expand_more</i>
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
                            <h2>
                                <?= Translate::td('Participants in level') ?>:
                                <?= count($referral->referrals) ?>
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
                            <h2>
                                <?= Translate::td('Participants in level') ?>:
                                <?= count($referral->compressed_referrals) ?>
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