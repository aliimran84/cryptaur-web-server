<?php

require __DIR__ . '/loader.php';

use core\engine\Application;
use core\engine\DB;

Application::init();

$users = DB::get("
    SELECT id, referrer_id
    FROM investors
    WHERE referrer_id > 0 AND referrer_id NOT IN (
      SELECT id FROM investors
    )
;");
foreach ($users as $i => $user) {
    $correctReferrerId = DB::get("
        SELECT investor_id from investors_to_previous_system where previoussystem_id = (
            SELECT invited_by_id
            FROM syndicates_syndicateaccount
            WHERE syndicates_syndicateaccount.account_id = (
              SELECT previoussystem_id
              FROM investors_to_previous_system
              WHERE investors_to_previous_system.investor_id = {$user['id']}
            )
        )
    ;")[0]['investor_id'];
    $users = DB::set("
        UPDATE investors
        SET referrer_id = $correctReferrerId
        WHERE id = {$user['id']}
    ;");
}