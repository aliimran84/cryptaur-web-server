<?php

require __DIR__ . '/loader.php';

use core\controllers\Investor_controller;
use core\engine\Application;
use core\models\Deposit;
use core\models\Investor;
use core\models\Wallet;

Application::init();

function randomEmail()
{
    return dechex(rand()) . '@test.test';
}

function randomPassword()
{
    return dechex(rand());
}

function randomAddr()
{
    return '0x' . sha1(rand());
}

$testCoin = 'DOGE';

$newInvestors_id = [0];
for ($i = 0; $i < 5000; $i++) {
    $newInvestor_id = Investor::registerUser(randomEmail(), randomAddr(), $newInvestors_id[array_rand($newInvestors_id)], Investor_controller::hashPassword('qwerty1'));
    $newInvestors_id[] = $newInvestor_id;
    Wallet::registerWallet($newInvestor_id, $testCoin, randomAddr());
    $amount = [1, rand(1, 20)][rand(0, 1)];
    for ($j = 0; $j < rand(0, 5); ++$j) {
        Deposit::receiveDeposit($amount, $testCoin, randomAddr(), 0, $newInvestor_id);
    }
}