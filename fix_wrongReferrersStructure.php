<?php

require __DIR__ . '/loader.php';

use core\engine\Application;
use core\engine\DB;

Application::init();

Application::setValue(\core\models\Investor::REGISTERING_IS_LOCKED_KEY, true);

$startTime = time();

$lastId = DB::get("SELECT `id` FROM `investors` ORDER BY `id` DESC LIMIT 1;")[0]['id'];
echo "Current last id: $lastId\r\n";

do {
    $investors_data = DB::get("SELECT * FROM `investors` WHERE `referrer_id` > `id`;");
    $count = count($investors_data);
    foreach ($investors_data as $i => $investor_data) {
        ++$lastId;
        DB::set("UPDATE `investors` SET `id` = '$lastId' WHERE `id` = {$investor_data['id']} LIMIT 1;");
        DB::set("UPDATE `investors` SET `referrer_id` = '$lastId' WHERE `referrer_id` = {$investor_data['id']};");
        DB::set("UPDATE `investors_to_previous_system` SET `investor_id` = '$lastId' WHERE `investor_id` = {$investor_data['id']} LIMIT 1;");
        DB::set("UPDATE `investors_referrals_compressed` SET `investor_id` = '$lastId' WHERE `investor_id` = {$investor_data['id']};");
        DB::set("UPDATE `deposits` SET `investor_id` = '$lastId' WHERE `investor_id` = {$investor_data['id']};");
        DB::set("UPDATE `wallets` SET `investor_id` = '$lastId' WHERE `investor_id` = {$investor_data['id']};");
        DB::set("UPDATE `eth_queue` SET `investor_id` = '$lastId' WHERE `investor_id` = {$investor_data['id']};");
        DB::set("UPDATE `eth_queue_wallets` SET `investor_id` = '$lastId' WHERE `investor_id` = {$investor_data['id']};");
        DB::set("UPDATE `investors_2fa_choice` SET `investor_id` = '$lastId' WHERE `investor_id` = {$investor_data['id']};");
        DB::set("UPDATE `investors_ethaddresses` SET `investor_id` = '$lastId' WHERE `investor_id` = {$investor_data['id']};");
        DB::set("UPDATE `investors_referrals_totals` SET `investor_id` = '$lastId' WHERE `investor_id` = {$investor_data['id']};");
        DB::set("UPDATE `investors_referrers` SET `investor_id` = '$lastId' WHERE `investor_id` = {$investor_data['id']};");
        DB::set("UPDATE `investors_stage_0_bounty` SET `investor_id` = '$lastId' WHERE `investor_id` = {$investor_data['id']};");
        DB::set("UPDATE `investors_waiting_tokens` SET `investor_id` = '$lastId' WHERE `investor_id` = {$investor_data['id']};");
        DB::set("
            SELECT *, TRIM(BOTH ',' FROM REPLACE(concat(',', `referrals`, ','), ',{$investor_data['id']},', ',$lastId,'))
            FROM `investors_referrals`
            where concat(',', `referrals`, ',') like '%,{$investor_data['id']},%';
        ;");
        DB::set("
            SELECT *, TRIM(BOTH ',' FROM REPLACE(concat(',', `referrers`, ','), ',{$investor_data['id']},', ',$lastId,'))
            FROM `investors_referrers`
            where concat(',', `referrers`, ',') like '%,{$investor_data['id']},%';
        ;");
        $duration = (time() - $startTime) + 1;
        $currentCount = $i;
        echo date('Y-m-d H:i:s') . ": fix for $i/$count (duration: {$duration}s)\r\n";
    }
} while ($count !== 0);

++$lastId;
DB::query("ALTER TABLE `investors` AUTO_INCREMENT = $lastId;");

$lastId = DB::get("SELECT `id` FROM `investors` ORDER BY `id` DESC LIMIT 1;")[0]['id'];
echo "New last id: $lastId\r\n";

Application::setValue(\core\models\Investor::REGISTERING_IS_LOCKED_KEY, false);