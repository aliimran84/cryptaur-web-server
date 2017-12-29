<?php

require __DIR__ . '/../loader.php';

use core\engine\Application;
use core\engine\DB;
use core\engine\Router;

Application::init();

Router::register(function () {
    $bountyInfo = @DB::get("
        SELECT
            count( * ) AS realized_bounty 
        FROM
            investors 
        WHERE
            eth_bounty = 0 
            AND eth_new_bounty > 0 UNION
        SELECT
            count( * ) AS with_bounty 
        FROM
            investors 
        WHERE
            eth_new_bounty > 0
    ")[0];
    echo "with_bounty: {$bountyInfo['with_bounty']}<br>";
    echo "realized_bounty {$bountyInfo['realized_bounty']}";
}, 'bounty_info');

call_user_func(Router::current());