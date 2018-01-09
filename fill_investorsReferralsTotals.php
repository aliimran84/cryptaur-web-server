<?php

require __DIR__ . '/loader.php';

use core\engine\Application;
use core\engine\DB;
use core\models\Coin;

Application::init();

$startTime = time();

$usersCount = DB::get("
    SELECT count(*) as `count` FROM `investors`
")[0]['count'];

echo "start {$usersCount}\r\n";

$token = Coin::token();
$query = "
    INSERT INTO `investors_referrals_totals` ( investor_id, coin )
    SELECT investors.id, '$token'
    FROM investors
    WHERE investors.id not in (select investor_id from investors_referrals_totals);
    ;\r\n";
foreach (Coin::coins() as $coin) {
    $query .= "
        INSERT INTO `investors_referrals_totals` ( investor_id, coin )
        SELECT investors.id, '$coin'
        FROM investors
        WHERE investors.id not in (select investor_id from investors_referrals_totals);
        ;\r\n";
}
$query .= "UPDATE `investors_referrals_totals` SET `sum` = 0;\r\n";
DB::multi_query($query);

$limitSize = 500;
for ($offset = 0; $offset < $usersCount; $offset += $limitSize) {
    $users = DB::get("
        SELECT
            *,
            (
                    SELECT CONCAT(
                            '[',
                            GROUP_CONCAT(JSON_OBJECT('coin', coin, 'balance', balance)),
                            ']'
                    ) FROM `wallets` WHERE `investor_id` = `investors`.`id`
            ) as `wallets`
        FROM
            `investors`
        ORDER BY `id`
        LIMIT $offset, $limitSize
    ;");

    $query = '';
    foreach ($users as $i => $user) {
        $query .= "
            UPDATE `investors_referrals_totals`
            SET `sum` = `sum` + {$user['tokens_count']}
            WHERE
                `coin` = '$token' AND
                FIND_IN_SET (`investor_id`, (
                    SELECT `referrers`
                    FROM `investors_referrers`
                    WHERE `investor_id` = {$user['id']}
                ))
        ;\r\n";
        $wallets = json_decode($user['wallets'], true);
        foreach ($wallets as $wallet) {
            $query .= "
                UPDATE `investors_referrals_totals`
                SET `sum` = `sum` + {$wallet['balance']}
                WHERE
                    `coin` = '{$wallet['coin']}' AND
                    FIND_IN_SET (`investor_id`, (
                        SELECT `referrers`
                        FROM `investors_referrers`
                        WHERE `investor_id` = {$user['id']}
                    ))
            ;";
        }
    }
    DB::multi_query($query);

    $duration = (time() - $startTime) + 1;
    $currentCount = $offset;
    $speed = number_format($currentCount / $duration, 5, '.', '');
    if ($speed == 0) {
        $remained = 1;
    } else {
        $remained = (int)($usersCount - $currentCount) / $speed;
    }
    echo date('Y-m-d H:i:s') . ": fill for $currentCount/$usersCount (duration: {$duration}s, speed: {$speed}u/s, remained: {$remained}s)\r\n";
}

echo "complete\r\n";