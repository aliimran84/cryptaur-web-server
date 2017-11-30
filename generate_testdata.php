<?php

require __DIR__ . '/loader.php';

use core\controllers\Investor_controller;
use core\engine\Application;
use core\models\Investor;

Application::init();

function randomEmail()
{
    return dechex(rand()) . '@test.test';
}

function randomPassword()
{
    return dechex(rand());
}

function randomEthAddr()
{
    return '0x' . sha1(rand());
}

$newInvestors_id = [0];
for ($i = 0; $i < 5000; $i++) {
    $newInvestors_id[] = Investor::registerUser(randomEmail(), randomEthAddr(), $newInvestors_id[array_rand($newInvestors_id)], Investor_controller::hashPassword('qwerty1'));
}