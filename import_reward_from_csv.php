<?php

require __DIR__ . '/loader.php';

use core\engine\Application;
use core\engine\DB;
use core\models\Bounty;
use core\models\Investor;

Application::init();

$innerCsv = $argv[1];

$i = 0;

$startTime = time();

if (($handle = fopen($innerCsv, 'r')) !== FALSE) {
    fgetcsv($handle, 1000, ","); // skip 1st line
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        ++$i;

        $id = (int)$data[0];
        $ref = (int)$data[1];
        $addr = $data[2];
        $eth = (double)$data[3];
        $btc = (double)$data[4];
        $cpt = (double)$data[5];
        $cashback = (double)$data[6];

        $id_2 = @(int)DB::get("
            UPDATE
                investors
                JOIN investors_to_previous_system ON investors_to_previous_system.investor_id = investors.id
                JOIN auth_user ON auth_user.id = investors_to_previous_system.previoussystem_id
            SET
                eth_new_bounty = ?,
                eth_bounty = (eth_bounty + ?)
            WHERE
                auth_user.id = ?
            LIMIT 1
        ;", [$cashback, $cashback, $id])[0]['id'];

        echo (time() - $startTime) . "s, num: $i, id: $id\r\n";
    }
    fclose($handle);
}