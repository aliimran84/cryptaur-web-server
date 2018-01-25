<?php

require __DIR__ . '/loader.php';

use core\engine\Application;
use core\engine\DB;
use core\engine\Utility;

Application::init();

$eth = Utility::int_string(DB::get("SELECT SUM(`balance`) AS `sum` FROM `wallets` WHERE `coin`='eth';")[0]['sum']);
$btc = Utility::int_string(DB::get("SELECT SUM(`balance`) AS `sum` FROM `wallets` WHERE `coin`='btc';")[0]['sum']);
$users_count = Utility::int_string(DB::get("SELECT COUNT(*) AS `count` FROM `investors`;")[0]['count']);

file_put_contents(\core\controllers\Base_controller::ICOINFO_FILE, json_encode([
    'total_eth' => $eth,
    'total_btc' => $btc,
    'total_users' => $users_count
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));