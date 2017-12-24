<?php

require __DIR__ . '/loader.php';

use core\engine\Application;
use core\engine\DB;

Application::init();

$startTime = time();

$startOffset = (int)($argv[1] | 0);

$usersCount = DB::get("
    SELECT
        count(auth_user.id) as count
    FROM
        auth_user
        JOIN account_account ON account_account.id = auth_user.id
    WHERE
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
for ($offset = $startOffset; $offset < $usersCount; $offset += $limitSize) {
    $users = DB::get("
        SELECT
            auth_user.id,
            auth_user.email
        FROM
            auth_user
            JOIN account_account ON account_account.id = auth_user.id
        WHERE
            AND account_account.email_confirmed = 1
        LIMIT $offset, $limitSize
    ;");

    foreach ($users as $i => $user) {
        $btc = (double)@DB::get("
            select sum(amount_to_exchange) as a from exchange_exchangeorder where
            source_currency='btc' and account_id=? and status=4 and `created_at`>?
        ;", [$user['id'], $lastCashbackedTs])[0]['a'];

        $eth = (double)@DB::get("
            select sum(amount_to_exchange) as a from exchange_exchangeorder where
            source_currency='eth' and account_id=? and status=4 and `created_at`>?
        ;", [$user['id'], $lastCashbackedTs])[0]['a'];

        // btc -> eth = 40.9254 fix на указанный момент
        $amount = $btc / 40.9254 + $eth;

        DB::get(
            "UPDATE `investors` SET `eth_not_used_in_bounty` = ? WHERE `email` = ? LIMIT 1;",
            [$amount, $user['email']]
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