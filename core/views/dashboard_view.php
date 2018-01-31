<?php

namespace core\views;

use core\controllers\Base_controller;
use core\controllers\Bounty_controller;
use core\controllers\Investor_controller;
use core\engine\Application;
use core\models\Bounty;
use core\models\Coin;
use core\models\EthQueue;
use core\models\Investor;
use core\models\Wallet;
use core\translate\Translate;

class Dashboard_view
{
    const BOUNTY_ERR = 'bounty_err';

    static public function view()
    {
        $icoInfo = Base_controller::icoInfo(false);
        ob_start();
        ?>

        <div class="row">
            <div class="col s12 main-panel">
                <div class="row indicators">
                    <div class="col s12 m6 l2">
                        <h4><?= Translate::td('Total tokens minted') ?></h4>
                        <h3><?= Coin::token() ?> <?= number_format($icoInfo['total_tokens'], 0, '.', '&nbsp;') ?></h3>
                    </div>
                    <div class="col s12 m6 l2">
                        <h4><?= Translate::td('Total participants') ?></h4>
                        <h3><?= number_format($icoInfo['total_users'], 0, '.', '&nbsp;') ?></h3>
                    </div>
                    <div class="col s12 m6 l2">
                        <h4><?= Translate::td('Total coin contributed', ['coin' => 'BTC']) ?></h4>
                        <h3>BTC <?= number_format($icoInfo['total_btc'], 0, '.', '&nbsp;') ?></h3>
                    </div>
                    <div class="col s12 m6 l2">
                        <h4><?= Translate::td('Total coin contributed', ['coin' => 'ETH']) ?></h4>
                        <h3>ETH <?= number_format($icoInfo['total_eth'], 0, '.', '&nbsp;') ?></h3>
                    </div>
                    <div class="col s12 m6 l2">
                        <h4><?= Translate::td('Total coin contributed', ['coin' => 'XEM']) ?></h4>
                        <h3>XEM <?= number_format($icoInfo['total_xem'], 0, '.', '&nbsp;') ?></h3>
                    </div>
                    <div class="col s12 m6 l2">
                        <h4><?= Translate::td('Total coin contributed', ['coin' => 'XRP']) ?></h4>
                        <h3>XRP <?= number_format($icoInfo['total_xrp'], 0, '.', '&nbsp;') ?></h3>
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
                            <?php
                            $pendingQueueTypes = EthQueue::pendingQueueTypesByInvestor(Application::$authorizedInvestor->id);
                            if (@$pendingQueueTypes[EthQueue::TYPE_MINT_REINVEST]) {
                                ?>
                                <div class="amount">
                                    <p class="blue-text"><?= Translate::td('Tokens minting is in queue') ?></p>
                                </div>
                                <?php
                            }
                            if (@$pendingQueueTypes[EthQueue::TYPE_SENDETH_WITHDRAW]) {
                                ?>
                                <div class="amount">
                                    <p class="blue-text"><?= Translate::td('Ether withdraw is in queue') ?></p>
                                </div>
                                <?php
                            }
                            if (@$pendingQueueTypes[EthQueue::TYPE_SENDETH_REINVEST]) {
                                ?>
                                <div class="amount">
                                    <p class="blue-text"><?= Translate::td('Ether reinvesting is in queue') ?></p>
                                </div>
                                <?php
                            }
                            ?>
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
            <div class="col s12 main-panel">
                <?= Wallet_view::newContribution() ?>
            </div>
        </div>
        <div class="row">
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
        <?php
        $pendingQueueTypes = EthQueue::pendingQueueTypesByInvestor(Application::$authorizedInvestor->id);
        if (@$pendingQueueTypes[EthQueue::TYPE_MINT_OLD_INVESTOR_INIT] || @$pendingQueueTypes[EthQueue::TYPE_MINT_DEPOSIT]) {
            ?>
            <div class="amount">
                <p class="blue-text"><?= Translate::td('Tokens minting is in queue') ?></p>
            </div>
            <?php
        }
        ?>
        <div class="amount input-field">
            <h5><?= Translate::td('Contributed') ?></h5>
            <ul>
                <?php
                foreach (Coin::coins() as $coin) {
                    $wallet = Wallet::getByInvestoridCoin(Application::$authorizedInvestor->id, $coin);
                    $balance = 0;
                    if (@$_SESSION['tester']) {
                        switch ($coin) {
                            case 'ETH':
                                $balance = 0.32;
                        }
                    } else if ($wallet) {
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

        <div class="col s12 main-panel">
            <section class="my-group">
                <div class="row">
                    <h3><?= Translate::td('My group') ?></h3>
                </div>
                <div class="row">
                    <div class="col s12 l4 main-panel-block">
                        <h3>
                            CPT:
                            <?= number_format(
                                Application::$authorizedInvestor->referrals_totals[Coin::token()] +
                                Application::$authorizedInvestor->tokens_count,
                                0, '.', '&nbsp;'
                            ) ?>
                        </h3>
                        <?php /*
                        <h3>BTC: <?= (int)Wallet::totalCoinsUsed('btc') ?> / ETH: <?= (int)Wallet::totalCoinsUsed('eth') ?></h3>
                        */ ?>
                    </div>
                    <div class="col s12 l4 main-panel-block">
                        <h3>
                            <?= Translate::td('Participants') ?>:
                            <?= Application::$authorizedInvestor->referralsCount() + 1 ?>
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
                                        <?php if (count(Application::$authorizedInvestor->referrals)) { ?>
                                            <div class="line-right"></div>
                                            <div class="participants-block">
                                                <h2>
                                                    <?= Translate::td('Participants in level') ?>:
                                                    <?= count(Application::$authorizedInvestor->referrals) ?>
                                                    <i class="material-icons">expand_more</i></h2>
                                            </div>
                                        <?php } ?>
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
        if (!@$_SESSION['modal_cryptauretherwallet-info']) {
            session_start();
            $_SESSION['modal_cryptauretherwallet-info'] = true;
            session_write_close();
            ?>
            <div id="modal_cryptauretherwallet-info" class="modal">
                <div class="modal-content">
                    <h4><?= Translate::td('ATTENTION') ?>!</h4>
                    <p>
                        <?= Translate::td('In order to protect the Cryptaur users and elevate the CPT and ether...') ?>
                    </p>
                    <p>
                        <?= Translate::td('Nevertheless, should you prefer to store your CPT tokens...') ?>
                    </p>
                    <p>
                        <?= Translate::td('PLEASE BE EXTREMELY CAREFUL AND ALWAYS REMEMBER ABOUT CYBER-SECURITY!') ?>
                    </p>
                    <br>
                    <button onclick="$('#modal_cryptauretherwallet-info').modal('close');" class="waves-effect waves-light btn">
                        <?= Translate::td('YES, I UNDERSTAND AND AGREE') ?>.
                    </button>
                </div>
            </div>
            <?php
        }
        ?>

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