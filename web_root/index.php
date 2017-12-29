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
}, 'bounty_info');

call_user_func(Router::current());