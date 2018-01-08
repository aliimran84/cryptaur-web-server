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

$limitSize = 5000;
for ($offset = 0; $offset < $usersCount; $offset += $limitSize) {
    $users = DB::get("
        SELECT
            *
        FROM
            `investors`
        ORDER BY `id`
        LIMIT $offset, $limitSize
    ;");

    foreach ($users as $i => $user) {
        DB::set("
            UPDATE investors_referrals_tokens 
            SET investors_referrals_tokens.tokens_count = investors_referrals_tokens.tokens_count + ? 
            WHERE
            investor_id IN (
                SELECT
                referrer_id 
                FROM
                (
                    SELECT referrer_id FROM investors,
                    ( SELECT @pv := ? ) initialisation
                    WHERE id = @pv AND @pv := referrer_id ORDER BY id DESC
                ) AS tmp 
            )
        ;", [$user['tokens_count'], $user['id']]);
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