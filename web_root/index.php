<?php

require __DIR__ . '/../loader.php';

use core\engine\Application;
use core\engine\DB;
use core\engine\Router;
use core\engine\Utility;
use core\models\Coin;
use core\models\Investor;

Application::init();

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

Router::register(function () {
    header('Content-type: text/html; charset=utf-8');
    ob_implicit_flush(true);
    ob_end_flush();
    $time_start = microtime_float();
    $investorsData = DB::get("SELECT * FROM INVESTORS");
    var_dump(count($investorsData));
    foreach ($investorsData as $i => $investorData) {
        $referrersChain = '/';
        foreach (array_reverse(Investor::referrersToRoot($investorData['id'])) as $refInvestor) {
            $referrersChain .= $refInvestor->id . '/';
        }
        DB::set(
            "REPLACE `investors_referrers` SET `id` = ?, `referrers_chain` = ?",
            [$investorData['id'], $referrersChain]
        );
        if ($i % 100 === 0) {
            var_dump($i, microtime_float() - $time_start);
        }
    }

//    var_dump($invsetor);
//    $referrersChain = '/';
//    foreach (array_reverse(Investor::referrersToRoot($invsetor->id)) as $refInvestor) {
//        $referrersChain .= $refInvestor->id . '/';
//    }
//    var_dump($referrersChain);
//    var_dump(Investor::summonedBy($invsetor, true));
    var_dump(microtime_float() - $time_start);
//    var_dump(\core\models\Bounty::rewardForReferrer($invsetor));
}, 'test');

Router::register(function () {
    $time_start = microtime_float();
    $investor = Investor::getById(876);
    $investor->initReferalls(1);
    var_dump(microtime_float() - $time_start);
    $investor->initCompressedReferalls(6);
    var_dump(microtime_float() - $time_start);
    \core\models\Bounty::rewardForInvestor($investor);
    var_dump(microtime_float() - $time_start);
}, 'test22');

Router::register(function () {
    $time_start = microtime_float();

    $invsetor = Investor::getById(60);

    $invsetor->fill_referalsCompressedTable();

    var_dump(microtime_float() - $time_start);
}, 'test3');

Router::register(function () {
    $time_start = microtime_float();

    var_dump(Investor::checkPreviousSystemCredentials('user.raa@gmail.com', 'qwe123'));

    var_dump(microtime_float() - $time_start);
}, 'p1');

Router::register(function () {
    $time_start = microtime_float();

    $invsetor = Investor::getById(109);
    $invsetor->initCompressedReferalls(6);

//    var_dump($invsetor);

    $r = [];
    $reward = \core\models\Bounty::rewardForInvestor($invsetor, $r);
    var_dump($reward);
    var_dump($r);
    var_dump($reward / Coin::getRate(Coin::COMMON_COIN));


    /*
    var_dump($invsetor->referralsCount());
    var_dump($invsetor->referralsCount());
    var_dump($invsetor->referralsCount());
    var_dump($invsetor->referralsCount());
    var_dump($invsetor->referralsCount());
    var_dump($invsetor->referralsCount());
    var_dump($invsetor->referralsCount());
    var_dump($invsetor->referralsCount());
    var_dump($invsetor->referralsCount());
    var_dump($invsetor->referralsCount());
    var_dump($invsetor->referralsCount());
     */
//    var_dump(\core\models\Bounty::rewardForInvestor($invsetor));
//    var_dump($invsetor->compressedReferralsCount());
//    var_dump($invsetor->compressedReferralsCount());
//    var_dump($invsetor->compressedReferralsCount());
//    var_dump($invsetor->compressedReferralsCount());
//    var_dump($invsetor->compressedReferralsCount());
//    var_dump($invsetor->compressedReferralsCount());
//    var_dump($invsetor->compressedReferralsCount());
//    var_dump($invsetor->compressedReferralsCount());
//    var_dump($invsetor->compressedReferralsCount());
//    var_dump($invsetor->compressedReferralsCount());
//    var_dump($invsetor->compressedReferralsCount());
//    var_dump($invsetor->initReferalls(6));
//    var_dump(\core\models\Bounty::rewardForInvestor($invsetor));
//    var_dump($invsetor->referralsCount());
//    var_dump($invsetor->referralsCount());
//    var_dump($invsetor->referralsCount());
//    var_dump($invsetor->referralsCount());
//    var_dump($invsetor->referralsCount());
    /*
    var_dump($invsetor->compressedReferralsCount());
    var_dump($invsetor->compressedReferralsCount());
    var_dump($invsetor->compressedReferralsCount());
    var_dump($invsetor->compressedReferralsCount());
    var_dump($invsetor->referralsCount());
    var_dump($invsetor->referralsCount());
    var_dump($invsetor->compressedReferralsCount());
    var_dump($invsetor->compressedReferralsCount());
    */

    var_dump(microtime_float() - $time_start);
}, 'test4');

Router::register(function () {
    $time_start = microtime_float();

    $invsetor = Investor::getById(109);
    $invsetor->eth_address = '0x17b75c9df7ef0666215a4be2be29e6e5cd0d1db5';

    $d = new \core\models\Deposit;
    $d->txid = '0x01fce6b6a7c796d257fd848e95ea63bc1fd2dae04d71db339e705f5693025042';
    $d->coin = 'ETH';
//    var_dump($d);

//    var_dump(\core\controllers\Bounty_controller::mintTokens($invsetor, 1.129403));
    var_dump(\core\controllers\Bounty_controller::sendEth($invsetor->eth_address, 0.129403));


    var_dump(microtime_float() - $time_start);
}, 'test5');

Router::register(function () {
    echo \core\views\Base_view::header();
    echo \core\views\Base_view::footer();
}, 'test/1');

Router::register(function () {
    if (@$_GET['to'] && @$_GET['msg']) {
        var_dump(\core\engine\Email::send($_GET['to'], [], $_GET['msg'], $_GET['msg']));
    }
}, 'email');

call_user_func(Router::current());