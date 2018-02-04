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
    DELETE FROM `investors_referrals_totals`;

     INSERT INTO `investors_referrals_totals` ( `investor_id`, `coin` )
    SELECT `investors`.`id`, '$token'
    FROM `investors`
    ;\r\n";
foreach (Coin::coins() as $coin) {
    $query .= "
        INSERT INTO `investors_referrals_totals` ( `investor_id`, `coin` )
        SELECT `investors`.`id`, '$coin'
        FROM `investors`
        ;\r\n";
}
DB::multi_query($query);

$limitSize = 500;
for ($offset = 0; $offset < $usersCount; $offset += $limitSize) {
    $users = DB::get("
        SELECT
            `id`, `tokens_count`,
            (
                SELECT CONCAT(
                        '[',
                        GROUP_CONCAT(JSON_OBJECT('coin', coin, 'balance', balance)),
                        ']'
                ) FROM `wallets` WHERE `investor_id` = `investors`.`id`
            ) as `wallets`,
            (
                SELECT `referrers` from `investors_referrers` where `investor_id` = `investors`.`id`
            ) as `referrers`
        FROM
            `investors`
        ORDER BY `id`
        LIMIT $offset, $limitSize
    ;");

    $query = '';
    foreach ($users as $i => $user) {
        $referrals = DB::get("select `referrals` from `investors_referrals` where `investor_id`=?", [$user['id']])[0]['referrals'];
        $ids = $user['id'];
        if ($referrals) {
            $ids .= ',' . $referrals;
        }

        DB::query("
            UPDATE `investors_referrals_totals`
            SET `sum` = (select sum(`tokens_count`) from `investors` where `id` in($ids))
            WHERE
                `coin` = '$token' AND
                `investor_id` = {$user['id']}
        ;");
        foreach (Coin::coins() as $coin) {
            DB::query("
                UPDATE `investors_referrals_totals`
                SET `sum` = (select sum(`balance`) from `wallets` where `coin`='$coin' and `investor_id` in($ids))
                WHERE
                    `coin` = '$coin' AND
                    `investor_id` = {$user['id']}
            ;");
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