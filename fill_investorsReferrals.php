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

DB::multi_query("
    INSERT INTO investors_referrals ( investor_id, referrals )
    SELECT id, '' 
    FROM investors
    WHERE id not in (select investor_id from investors_referrals);

    UPDATE `investors_referrals`
    SET `referrals` = '';
");

$limitSize = 500;
for ($offset = 0; $offset < $usersCount; $offset += $limitSize) {
    $users = DB::get("
        SELECT
            `id`
        FROM
            `investors`
        ORDER BY `id`
        LIMIT $offset, $limitSize
    ;");

    $query = '';

    foreach ($users as $i => $user) {
        $query .= "
            UPDATE `investors_referrals` SET `referrals` = IF(`referrals` = '', {$user['id']}, concat(`referrals`, ',', {$user['id']}))
            WHERE `investor_id` IN (
                SELECT `referrers`
                FROM `investors_referrers`
                WHERE `investor_id` = {$user['id']}
            )
        ;\r\n";
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