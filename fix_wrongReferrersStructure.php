<?php

require __DIR__ . '/loader.php';

use core\engine\Application;
use core\engine\DB;

Application::init();

$startTime = time();

$lastId = DB::get("SELECT `id` FROM `investors` ORDER BY `id` DESC LIMIT 1;")[0]['id'];
echo "Current last id: $lastId\r\n";

do {
    $investors_data = DB::get("SELECT * FROM `investors` WHERE `referrer_id` > `id`;");
    $count = count($investors_data);
    foreach ($investors_data as $i => $investor_data) {
        ++$lastId;
        // todo: update several tables
        // eth_queue.investor_id
        // eth_queue_wallets.investor_id
        // investors_2fa_choice.investor_id
        // investors_ethaddresses.investor_id
        // investors_referrals.referrals concatenated str
        // investors_referrals_tokens.investor_id
        // investors_referrals_totals.investor_id
        // investors_referrers.investor_id
        // investors_referrers.referrers concatenated str
        // investors_stage_0_bounty.investor_id
        // investors_waiting_tokens.investor_id
        // example for concatenated form
        DB::get("
            SELECT *, TRIM(BOTH ',' FROM REPLACE(concat(',', `referrals`, ','), ',{$investor_data['id']},', ',$lastId,'))
            FROM `investors_referrals`
            where concat(',', `referrals`, ',') like '%,{$investor_data['id']},%';
        ;");
        DB::set("UPDATE `investors` SET `id` = '$lastId' WHERE `id` = {$investor_data['id']} LIMIT 1;");
        DB::set("UPDATE `investors_to_previous_system` SET `investor_id` = '$lastId' WHERE `investor_id` = {$investor_data['id']} LIMIT 1;");
        DB::set("UPDATE `investors_referrals_compressed` SET `investor_id` = '$lastId' WHERE `investor_id` = {$investor_data['id']};");
        DB::set("UPDATE `deposits` SET `investor_id` = '$lastId' WHERE `investor_id` = {$investor_data['id']};");
        DB::set("UPDATE `wallets` SET `investor_id` = '$lastId' WHERE `investor_id` = {$investor_data['id']};");
        $duration = (time() - $startTime) + 1;
        $currentCount = $i;
        echo date('Y-m-d H:i:s') . ": fix for $i/$count (duration: {$duration}s)\r\n";
    }
} while ($count !== 0);

++$lastId;
DB::query("ALTER TABLE `investors` AUTO_INCREMENT = $lastId;");

$lastId = DB::get("SELECT `id` FROM `investors` ORDER BY `id` DESC LIMIT 1;")[0]['id'];
echo "New last id: $lastId\r\n";