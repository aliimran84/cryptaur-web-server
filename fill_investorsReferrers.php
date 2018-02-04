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

$limitSize = 1000;
for ($offset = 0; $offset < $usersCount; $offset += $limitSize) {
    $users = DB::get("
        SELECT
            `id`
        FROM
            `investors`
        ORDER BY `id`
        LIMIT $offset, $limitSize
    ;");

    foreach ($users as $i => $user) {
        DB::set("
            INSERT INTO `investors_referrers` (`investor_id`, `referrers`)
            VALUES
            (
                ?,
                (
                    SELECT CAST(referrers as CHAR(10000)) as referrers FROM (
                        SELECT referrer_id, @g := IF(@g = '', referrer_id, concat(@g, ',', referrer_id)) as referrers
                        FROM
                                `investors`,
                                ( SELECT @g := '' ) AS `tmp`,
                                ( SELECT @pv :=  ? ) AS `initialisation`
                        WHERE `id` = @pv AND @pv := `referrer_id`
                        ORDER BY `id` DESC
                    ) AS tmp
                    ORDER BY referrer_id ASC
                    LIMIT 1
                )
            )
            ;", [
                $user['id'], $user['id']
            ]
        );
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