<?php

require __DIR__ . '/loader.php';

use core\engine\Application;
use core\engine\DB;

Application::init();

$startTime = time();

$usersCount = DB::get("
    SELECT
        count(auth_user.id) as count
    FROM
        auth_user
        JOIN account_account ON account_account.id = auth_user.id
    WHERE
        auth_user.email NOT LIKE '%+%'
        AND account_account.email_confirmed = 1
")[0]['count'];

$lastCashbackedTs = DB::get("
    select created_at from exchange_exchangeorder where id = (
        select order_id from syndicates_syndicateexchangeorder where id = (
           select order_id from syndicates_cashbackbonus where status = 3 order by id desc limit 1
        )
    )
")[0]['created_at'];

$limitSize = 1000;
for ($offset = 0; $offset < $usersCount; $offset += $limitSize) {
    $users = DB::get("
        SELECT
            auth_user.id,
            auth_user.email
        FROM
            auth_user
            JOIN account_account ON account_account.id = auth_user.id
        WHERE
            auth_user.email NOT LIKE \"%+%\"
            AND account_account.email_confirmed = 1
        LIMIT $offset, $limitSize
    ;");
//        AND auth_user.is_staff = 0

    foreach ($users as $i => $user) {
        $tokens_not_used_in_bounty = (double)@DB::get("
            select sum(amount)/100000000 as tokens from transactions_history where
            account_id=? and type=0 and `timestamp`>?
        ;", [$user['id'], $lastCashbackedTs])[0]['tokens'];

        DB::get(
            "UPDATE `investors` SET `tokens_not_used_in_bounty` = ? WHERE `email` = ? LIMIT 1;",
            [$tokens_not_used_in_bounty, $user['email']]
        );

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