<?php

require __DIR__ . '/loader.php';

use core\engine\Application;
use core\engine\DB;

Application::init();

$startTime = time();

$btcToEthFixRate = 40.9254;

$startOffset = (int)($argv[1] | 0);

$usersCount = DB::get("
    SELECT
        count(auth_user.id) as count
    FROM
        auth_user
        JOIN account_account ON account_account.id = auth_user.id
    WHERE
        account_account.email_confirmed = 1
        AND (auth_user.first_name != 'Vision' or auth_user.last_name != 'User')
")[0]['count'];

$lastCashbackedTs = DB::get("
    select created_at from exchange_exchangeorder where id = (
        select order_id from syndicates_syndicateexchangeorder where id = (
           select order_id from syndicates_cashbackbonus where status = 3 order by id desc limit 1
        )
    )
")[0]['created_at']; // 1510057754

$limitSize = 5000;
for ($offset = $startOffset; $offset < $usersCount; $offset += $limitSize) {
    $users = DB::get("
        SELECT
            auth_user.id,
            auth_user.email,
            investors_to_previous_system.investor_id
        FROM
            auth_user
            JOIN account_account ON account_account.id = auth_user.id
            JOIN investors_to_previous_system ON investors_to_previous_system.previoussystem_id = auth_user.id 
        WHERE
            account_account.email_confirmed = 1
            AND (auth_user.first_name != 'Vision' or auth_user.last_name != 'User')
        LIMIT $offset, $limitSize
    ;");

    foreach ($users as $i => $user) {
        $bounty_remains = (double)@DB::get("
            SELECT (
                (
                    SELECT sum( amount ) AS amount
                    FROM syndicates_cashbackbonus
                    WHERE
                        recipient_id = 52 AND
                        STATUS = 3
                ) -
                (
                    SELECT sum( amount ) / 1000000000000000000 AS amount
                    FROM transactions_history
                    WHERE
                        account_id = 52 AND
                        type = 1
                ) 
            ) AS eth_bounty
        ;");

        DB::set(
            "UPDATE `investors` SET `eth_bounty` = ? WHERE `id` = ? LIMIT 1;",
            [$bounty_remains, $user['investor_id']]
        );

        if ($i === 0) {
            $duration = (time() - $startTime) + 1;
            $currentCount = $i + $offset;
            $speed = number_format($currentCount / $duration, 5);
            if ($speed === 0) {
                $remained = 1;
            } else {
                $remained = (int)($usersCount - $currentCount) / $speed;
            }
            echo date('Y-m-d H:i:s') . ": fill for $currentCount/$usersCount (userid: {$user['id']}, duration: {$duration}s, speed: {$speed}u/s, remained: {$remained}s)\r\n";
        }
    }
}