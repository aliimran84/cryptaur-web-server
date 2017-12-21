<?php

require __DIR__ . '/loader.php';

use core\engine\Application;
use core\engine\DB;
use core\models\Bounty;
use core\models\Coin;
use core\models\Investor;

Application::init();

$innerCsv = $argv[1];

$i = 0;

$startTime = time();

$fp = fopen("$startTime.csv", 'w');
if (($handle = fopen($innerCsv, 'r')) !== FALSE) {
    fgetcsv($handle, 1000, ","); // skip 1st line
    fputcsv($fp, [
        'Account Id', 'Parent Id', 'address', 'ETH', 'BTC',
        'CPT', 'CPT_2', 'Round2CPT_2',
        'cashback', 'cashback_2', 'NOT_USED_CASHBACK_2',
        'Email_2',
        'LEVEL_1_CASHBACK_2',
        'LEVEL_2_CASHBACK_2',
        'LEVEL_3_CASHBACK_2',
        'LEVEL_4_CASHBACK_2',
        'LEVEL_5_CASHBACK_2',
        'LEVEL_6_CASHBACK_2'
    ]);
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        ++$i;

        $id = (int)$data[0];
        $ref = (int)$data[1];
        $addr = $data[2];
        $eth = (double)$data[3];
        $btc = (double)$data[4];
        $cpt = (double)$data[5];
        $cashback = (double)$data[6];

        $id = @(int)DB::get("
            SELECT
                investors.id 
            FROM
                auth_user
                JOIN investors ON investors.email = auth_user.email 
            WHERE
                auth_user.id = ?
            LIMIT 1
        ;", [$id])[0]['id'];

        $investor = Investor::getById($id);
        if (is_null($investor)) {
            continue;
        }

        $rewardByLevel = [];
        $rewardUsd = Bounty::rewardForInvestor($investor, $rewardByLevel);

        fputcsv($fp, [
            $id, $ref, $addr, $eth, $btc,
            $cpt, $investor->tokens_count, $investor->tokens_not_used_in_bounty,
            $cashback, Coin::convert($rewardUsd, Coin::USD, Coin::COMMON_COIN), $investor->eth_bounty,
            $investor->email,
            Coin::convert($rewardByLevel[1], Coin::USD, Coin::COMMON_COIN),
            Coin::convert($rewardByLevel[2], Coin::USD, Coin::COMMON_COIN),
            Coin::convert($rewardByLevel[3], Coin::USD, Coin::COMMON_COIN),
            Coin::convert($rewardByLevel[4], Coin::USD, Coin::COMMON_COIN),
            Coin::convert($rewardByLevel[5], Coin::USD, Coin::COMMON_COIN),
            Coin::convert($rewardByLevel[6], Coin::USD, Coin::COMMON_COIN)
        ]);
        echo (time() - $startTime) . "s, num: $i, id: $id\r\n";
    }
    fclose($handle);
}
fclose($fp);