<?php

require __DIR__ . '/loader.php';

use core\engine\Application;
use core\engine\DB;

Application::init();

$eth = DB::get("SELECT SUM(`balance`) AS `sum` FROM `wallets` WHERE `coin`='eth';")[0]['sum'];
$btc = DB::get("SELECT SUM(`balance`) AS `sum` FROM `wallets` WHERE `coin`='btc';")[0]['sum'];

file_put_contents(\core\controllers\Base_controller::ICOINFO_FILE, json_encode([
    'total_eth' => $eth,
    'total_btc' => $btc
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));