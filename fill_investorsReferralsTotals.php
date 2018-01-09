<?php

require __DIR__ . '/loader.php';

use core\engine\Application;
use core\engine\DB;

Application::init();

$startTime = time();

$usersCount = DB::get("
    SELECT count(*) as `count` FROM `investors`
")[0]['count'];

echo "start {$usersCount}\r\n";

DB::set("
    UPDATE `investors_referrals_totals`
    SET `sum` = 0
;");

$limitSize = 1000;
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

    foreach ($users as $i => $user) {
        DB::set("
            UPDATE `investors_referrals_totals`
            SET `sum` = `sum` + ?
            WHERE
                `coin` = ? AND
                `investor_id` IN (
                    SELECT `referrers`
                    FROM `investors_referrers`
                    WHERE `investor_id` = ?
                )
        ;", [$user['tokens_count'], $user['id']]);
        $wallets = json_decode($user['wallets'], true);
        foreach ($wallets as $wallet) {
            DB::set("
                UPDATE `investors_referrals_totals`
                SET `sum` = `sum` + ?
                WHERE
                    `coin` = ? AND
                    `investor_id` IN (
                        SELECT `referrers`
                        FROM `investors_referrers`
                        WHERE `investor_id` = ?
                    )
            ;", [$wallet['balance'], $user['id']]);
        }
    }
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