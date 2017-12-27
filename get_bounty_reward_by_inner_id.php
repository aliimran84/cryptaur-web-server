<?php

require __DIR__ . '/loader.php';

use core\engine\Application;
use core\engine\DB;
use core\models\Bounty;
use core\models\Coin;
use core\models\Investor;

Application::init();

$id = (int)$argv[1];
if ($id < 0) {
    $id = @(int)DB::get("
        SELECT
            investors.id 
        FROM
            auth_user
            JOIN investors ON investors.email = auth_user.email 
        WHERE
            auth_user.id = ?
    ", [-$id])[0]['id'];
}

$i = Investor::getById($id);
if (is_null($i)) {
    echo "Investor not exist\r\n";
    return;
}

echo "Email: {$i->email}\r\n";

echo "Tokens amount: {$i->tokens_count}\r\n";

echo "Not used bounty: {$i->eth_bounty}\r\n";

$rewardByLevel = [];
$rewardUsd = Bounty::rewardForInvestor($i, $rewardByLevel);
echo "Bounty USD: ";
var_dump($rewardUsd);

echo "Bounty CPT: ";
var_dump(Coin::convert($rewardUsd, Coin::USD, Coin::token()));

echo "Bounty ETH: ";
var_dump(Coin::convert($rewardUsd, Coin::USD, Coin::COMMON_COIN));

for ($i = 1; $i <= 6; ++$i) {
    echo "Bounty ETH level $i: ";
    var_dump(Coin::convert($rewardByLevel[$i], Coin::USD, Coin::COMMON_COIN));
}