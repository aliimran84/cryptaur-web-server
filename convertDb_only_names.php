<?php

require __DIR__ . '/loader.php';

use core\engine\Application;
use core\engine\DB;

Application::init();

$startTime = time();

$users_id = DB::get("
    select previoussystem_id from investors_to_previous_system where investor_id in (
        select id from investors where firstname like '?%'
    )
");
$count = count($users_id);
foreach ($users_id as $i => $user_id) {
    DB::set(
        "update investors
join investors_to_previous_system on investors_to_previous_system.investor_id=investors.id
join auth_user on investors_to_previous_system.previoussystem_id=auth_user.id
set investors.firstname=auth_user.first_name, investors.lastname=auth_user.first_name
where auth_user.id = ?",
        [$user_id['previoussystem_id']]
    );

    $duration = (time() - $startTime) + 1;
    echo date('Y-m-d H:i:s') . ": fill for $i/$count (duration: {$duration}s)\r\n";
}