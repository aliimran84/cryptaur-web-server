<?php

require __DIR__ . '/../loader.php';

use core\engine\Application;
use core\engine\DB;
use core\engine\Router;

Application::init();

Router::register(function () {
    $bountyInfo = @DB::get("
        SELECT
            ( SELECT count( * ) FROM investors WHERE eth_bounty = 0 AND eth_new_bounty > 0 ) AS realized_bounty,
            ( SELECT count( * ) FROM investors WHERE eth_new_bounty > 0 ) AS with_bounty
    ")[0];
    echo "with_bounty: {$bountyInfo['with_bounty']}<br>";
    echo "realized_bounty {$bountyInfo['realized_bounty']}";
}, 'bounty_info');

call_user_func(Router::current());