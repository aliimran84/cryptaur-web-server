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

    echo "eth_queue pending: $pending, mintPending: $mintPending";
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

call_user_func(Router::current());